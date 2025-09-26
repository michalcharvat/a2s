<?php

/*
 * ASCIIToSVG.php: ASCII diagram -> SVG art generator.
 * Copyright © 2012 Devon H. O'Dell <devon.odell@gmail.com>
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions
 * are met:
 *
 *  o Redistributions of source code must retain the above copyright notice,
 *    this list of conditions and the following disclaimer.
 *  o Redistributions in binary form must reproduce the above copyright notice,
 *    this list of conditions and the following disclaimer in the documentation
 *    and/or other materials provided with the distribution.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS"
 * AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE
 * IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE
 * ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT HOLDER OR CONTRIBUTORS BE
 * LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR
 * CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF
 * SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS
 * INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN
 * CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
 * ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
 * POSSIBILITY OF SUCH DAMAGE.
 *
 */

namespace MichalCharvat\A2S;

/*
  Copyright 2006 Wez Furlong, OmniTI Computer Consulting, Inc.
  Based on JLex which is:

       JLEX COPYRIGHT NOTICE, LICENSE, AND DISCLAIMER
  Copyright 1996-2000 by Elliot Joel Berk and C. Scott Ananian

  Permission to use, copy, modify, and distribute this software and its
  documentation for any purpose and without fee is hereby granted,
  provided that the above copyright notice appear in all copies and that
  both the copyright notice and this permission notice and warranty
  disclaimer appear in supporting documentation, and that the name of
  the authors or their employers not be used in advertising or publicity
  pertaining to distribution of the software without specific, written
  prior permission.

  The authors and their employers disclaim all warranties with regard to
  this software, including all implied warranties of merchantability and
  fitness. In no event shall the authors or their employers be liable
  for any special, indirect or consequential damages or any damages
  whatsoever resulting from loss of use, data or profits, whether in an
  action of contract, negligence or other tortious action, arising out
  of or in connection with the use or performance of this software.
  **************************************************************
*/

class ASCIIToSVG
{
    public bool $blurDropShadow = true;
    public string $fontFamily = "Consolas,Monaco,Anonymous Pro,Anonymous,Bitstream Sans Mono,monospace";

    /**
     * @var array<int, array<int, string>>
     */
    private array $grid;

    private SVGGroup $svgObjects;
    private array $clearCorners;

    private ?array $commands;

    /* Directions for traversing lines in our grid */
    const DIR_UP = 0x1;
    const DIR_DOWN = 0x2;
    const DIR_LEFT = 0x4;
    const DIR_RIGHT = 0x8;
    const DIR_NE = 0x10;
    const DIR_SE = 0x20;

    public function __construct(string $data)
    {
        CustomObjects::loadObjects();

        $this->clearCorners = [];

        /**
         * Parse out any command references. These need to be at the bottom of the
         * diagram due to the way they're removed. Format is:
         * [identifier] optional-colon optional-spaces ({json-blob})\n
         *
         * The JSON blob may not contain objects as values or the regex will break.
         */
        $this->commands = [];
        preg_match_all('/^\[([^\]]+)\]:?\s+({[^}]+?})/ims', $data, $matches);
        $bound = count($matches[1]);
        for ($i = 0; $i < $bound; $i++) {
            $this->commands[$matches[1][$i]] = json_decode($matches[2][$i], true);
        }

        $data = preg_replace('/^\[([^\]]+)\](:?)\s+.*/ims', '', $data);

        /**
         * Treat our UTF-8 field as a grid and store each character as a point in
         * that grid. The (0, 0) coordinate on this grid is top-left, just as it
         * is in images.
         */
        /** @var array<int, string> $explodedRows */
        $explodedRows = (array)explode("\n", $data);

        foreach ($explodedRows as $k => $line) {
            $this->grid[$k] = preg_split('//u', $line, -1, PREG_SPLIT_NO_EMPTY);
        }

        $this->svgObjects = new SVGGroup();
    }

    /*
   * This is kind of a stupid and hacky way to do this, but this allows setting
   * the default scale of one grid space on the X and Y axes.
   */
    public function setDimensionScale(int $x, int $y): void
    {
        $o = Scale::getInstance();
        $o->setScale($x, $y);
    }

    /* Render out what we've done!  */
    public function render(): string
    {
        $o = Scale::getInstance();

        /* Figure out how wide we need to make the canvas */
        $canvasWidth = 0;
        foreach ($this->grid as $line) {
            if (count($line) > $canvasWidth) {
                $canvasWidth = count($line);
            }
        }

        $canvasWidth = $canvasWidth * $o->xScale + 10;
        $canvasHeight = count($this->grid) * $o->yScale;

        /*
     * Boilerplate header with definitions that we might be using for markers
     * and drop shadows.
     */
        /*
<?xml version="1.0" encoding="UTF-8" standalone="no"?>
<!DOCTYPE svg PUBLIC "-//W3C//DTD SVG 1.1//EN"
  "http://www.w3.org/Graphics/SVG/1.1/DTD/svg11.dtd">
<!-- Created with ASCIIToSVG (https://github.com/dhobsd/asciitosvg/) -->
*/
        $out = <<<SVG
width="{$canvasWidth}px" height="{$canvasHeight}px"
viewBox="0 0 {$canvasWidth} {$canvasHeight}" version="1.1"
  xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink">
  <!-- Created with ASCIIToSVG (https://github.com/dhobsd/asciitosvg/) -->
  <defs>
    <filter id="dsFilterNoBlur" width="150%" height="150%">
      <feOffset result="offOut" in="SourceGraphic" dx="3" dy="3"/>
      <feColorMatrix result="matrixOut" in="offOut" type="matrix" values="0.2 0 0 0 0 0 0.2 0 0 0 0 0 0.2 0 0 0 0 0 1 0"/>
      <feBlend in="SourceGraphic" in2="matrixOut" mode="normal"/>
    </filter>
    <filter id="dsFilter" width="150%" height="150%">
      <feOffset result="offOut" in="SourceGraphic" dx="3" dy="3"/>
      <feColorMatrix result="matrixOut" in="offOut" type="matrix" values="0.2 0 0 0 0 0 0.2 0 0 0 0 0 0.2 0 0 0 0 0 1 0"/>
      <feGaussianBlur result="blurOut" in="matrixOut" stdDeviation="3"/>
      <feBlend in="SourceGraphic" in2="blurOut" mode="normal"/>
    </filter>
    <marker id="iPointer"
      viewBox="0 0 10 10" refX="5" refY="5"
      markerUnits="strokeWidth"
      markerWidth="8" markerHeight="7"
      fill="black"
      orient="auto">
      <path d="M 10 0 L 10 10 L 0 5 z" />
    </marker>
    <marker id="Pointer"
      viewBox="0 0 10 10" refX="5" refY="5"
      markerUnits="strokeWidth"
      markerWidth="8" markerHeight="7"
      fill="black"
      orient="auto">
      <path d="M 0 0 L 10 5 L 0 10 z" />
    </marker>
  </defs>
SVG;

        /* Render the group, everything lives in there */
        $out .= $this->svgObjects->render();

        $out .= "</svg>\n";

        return $out;
    }

    /*
   * Parsing the grid is a multi-step process. We parse out boxes first, as
   * this makes it easier to then parse lines. By parse out, I do mean we
   * parse them and then remove them. This does mean that a complete line
   * will not travel along the edge of a box, but you probably won't notice
   * unless the box is curved anyway. While edges are removed, points are
   * not. This means that you can cleanly allow lines to intersect boxes
   * (as long as they do not bisect!
   *
   * After parsing boxes and lines, we remove the corners from the grid. At
   * this point, all we have left should be text, which we can pick up and
   * place.
   */
    public function parseGrid(): void
    {
        $this->parseBoxes();
        $this->parseLines();

        foreach ($this->clearCorners as $corner) {
            $this->grid[$corner[0]][$corner[1]] = ' ';
        }

        $this->parseText();

        $this->injectCommands();
    }

    /*
   * Ahh, good ol' box parsing. We do this by scanning each row for points and
   * attempting to close the shape. Since the approach is first horizontal,
   * then vertical, we complete the shape in a clockwise order (which is
   * important for the Bezier curve generation.
   */
    private function parseBoxes(): void
    {
        /* Set up our box group  */
        $this->svgObjects->pushGroup('boxes');
        $this->svgObjects->setOption('stroke', 'black');
        $this->svgObjects->setOption('stroke-width', '2');
        $this->svgObjects->setOption('fill', 'none');

        /* Scan the grid for corners */
        foreach ($this->grid as $row => $line) {
            foreach ($line as $col => $char) {
                if ($this->isCorner($char)) {
                    $path = new SVGPath();

                    if ($char === '.' || $char === "'") {
                        $path->addPoint($col, $row, Point::CONTROL);
                    } else {
                        $path->addPoint($col, $row);
                    }

                    /**
                     * The wall follower is a left-turning, marking follower. See that
                     * function for more information on how it works.
                     */
                    $this->wallFollow($path, $row, $col + 1, self::DIR_RIGHT);

                    /* We only care about closed polygons */
                    if ($path->isClosed()) {
                        $path->orderPoints();

                        $skip = false;
                        /**
                         * The walking code can find the same box from a different edge:
                         *
                         * +---+   +---+
                         * |   |   |   |
                         * |   +---+   |
                         * +-----------+
                         *
                         * so ignore adding a box that we've already added.
                         */
                        foreach ($this->svgObjects->getGroup('boxes') as $box) {
                            $bP = $box->getPoints();
                            $pP = $path->getPoints();
                            $pPoints = count($pP);
                            $shared = 0;

                            /**
                             * If the boxes don't have the same number of edges, they
                             * obviously cannot be the same box.
                             */
                            if (count($bP) !== $pPoints) {
                                continue;
                            }

                            /* Traverse the vertices of this new box... */
                            for ($i = 0; $i < $pPoints; $i++) {
                                /* ...and find them in this existing box. */
                                for ($j = 0; $j < $pPoints; $j++) {
                                    if ($pP[$i]->x === $bP[$j]->x && $pP[$i]->y === $bP[$j]->y) {
                                        $shared++;
                                    }
                                }
                            }

                            /* If all the edges are in common, it's the same shape. */
                            if ($shared === count($bP)) {
                                $skip = true;
                                break;
                            }
                        }

                        if ($skip === false) {
                            /* Search for any references for styling this polygon; add it */
                            if ($this->blurDropShadow) {
                                $path->setOption('filter', 'url(#dsFilter)');
                            } else {
                                $path->setOption('filter', 'url(#dsFilterNoBlur)');
                            }

                            $name = $this->findCommands($path);

                            $this->svgObjects->addObject($path);
                        }
                    }
                }
            }
        }

        /**
         * Once we've found all the boxes, we want to remove them from the grid so
         * that they don't confuse the line parser. However, we don't remove any
         * corner characters because these might be shared by lines.
         */
        foreach ($this->svgObjects->getGroup('boxes') as $box) {
            $this->clearObject($box);
        }

        /* Anything after this is not a subgroup */
        $this->svgObjects->popGroup();
    }

    /*
   * Our line parser operates differently than the polygon parser. This is
   * because lines are not intrinsically marked with starting points (markers
   * are optional) -- they just sort of begin. Additionally, so that markers
   * will work, we can't just construct a line from some random point: we need
   * to start at the correct edge.
   *
   * Thus, the line parser traverses vertically first, then horizontally. Once
   * a line is found, it is cleared immediately (but leaving any control points
   * in case there were any intersections.
   */
    private function parseLines(): void
    {
        /* Set standard line options */
        $this->svgObjects->pushGroup('lines');
        $this->svgObjects->setOption('stroke', 'black');
        $this->svgObjects->setOption('stroke-width', '2');
        $this->svgObjects->setOption('fill', 'none');

        /* The grid is not uniform, so we need to determine the longest row. */
        $maxCols = 0;
        $bound = count($this->grid);
        for ($r = 0; $r < $bound; $r++) {
            $maxCols = max($maxCols, count($this->grid[$r]));
        }

        for ($c = 0; $c < $maxCols; $c++) {
            for ($r = 0; $r < $bound; $r++) {
                /* This gets set if we find a line-start here. */
                $dir = false;

                $line = new SVGPath();

                /*
         * Since the column count isn't uniform, don't attempt to handle any
         * rows that don't extend out this far.
         */
                if (!isset($this->grid[$r][$c])) {
                    continue;
                }

                $char = $this->getChar($r, $c);
                switch ($char) {
                    /**
                     * Do marker characters first. These are the easiest because they are
                     * basically guaranteed to represent the start of the line.
                     */
                    case '<':
                        $e = $this->getChar($r, $c + 1);
                        if ($this->isEdge($e, self::DIR_RIGHT) || $this->isCorner($e)) {
                            $line->addMarker($c, $r, Point::IMARKER);
                            $dir = self::DIR_RIGHT;
                        } else {
                            $se = $this->getChar($r + 1, $c + 1);
                            $ne = $this->getChar($r - 1, $c + 1);
                            if ($se === "\\") {
                                $line->addMarker($c, $r, Point::IMARKER);
                                $dir = self::DIR_SE;
                            } elseif ($ne === '/') {
                                $line->addMarker($c, $r, Point::IMARKER);
                                $dir = self::DIR_NE;
                            }
                        }
                        break;
                    case '^':
                        $s = $this->getChar($r + 1, $c);
                        if ($this->isEdge($s, self::DIR_DOWN) || $this->isCorner($s)) {
                            $line->addMarker($c, $r, Point::IMARKER);
                            $dir = self::DIR_DOWN;
                        } elseif ($this->getChar($r + 1, $c + 1) === "\\") {
                            /* Don't need to check west for diagonals. */
                            $line->addMarker($c, $r, Point::IMARKER);
                            $dir = self::DIR_SE;
                        }
                        break;
                    case '>':
                        $w = $this->getChar($r, $c - 1);
                        if ($this->isEdge($w, self::DIR_LEFT) || $this->isCorner($w)) {
                            $line->addMarker($c, $r, Point::IMARKER);
                            $dir = self::DIR_LEFT;
                        }
                        /* All diagonals come from west, so we don't need to check */
                        break;
                    case 'v':
                        $n = $this->getChar($r - 1, $c);
                        if ($this->isEdge($n, self::DIR_UP) || $this->isCorner($n)) {
                            $line->addMarker($c, $r, Point::IMARKER);
                            $dir = self::DIR_UP;
                        } elseif ($this->getChar($r - 1, $c + 1) === '/') {
                            $line->addMarker($c, $r, Point::IMARKER);
                            $dir = self::DIR_NE;
                        }
                        break;

                    /**
                     * Edges are handled specially. We have to look at the context of the
                     * edge to determine whether it's the start of a line. A vertical edge
                     * can appear as the start of a line in the following circumstances:
                     *
                     * +-------------      +--------------     +----    | (s)
                     * |                   |                   |        |
                     * |      | (s)        +-------+           |(s)     |
                     * +------+                    | (s)
                     *
                     * From this we can extrapolate that we are a starting edge if our
                     * southern neighbor is a vertical edge or corner, but we have no line
                     * material to our north (and vice versa). This logic does allow for
                     * the southern / northern neighbor to be part of a separate
                     * horizontal line.
                     */
                    case ':':
                        $line->setOption('stroke-dasharray', '5 5');
                    /* FALLTHROUGH */
                    case '|':
                        $n = $this->getChar($r - 1, $c);
                        $s = $this->getChar($r + 1, $c);
                        if (($s === '|' || $s === ':' || $this->isCorner($s)) &&
                            $n != '|' && $n != ':' && !$this->isCorner($n) &&
                            $n != '^') {
                            $dir = self::DIR_DOWN;
                        } elseif (($n === '|' || $n === ':' || $this->isCorner($n)) &&
                            $s != '|' && $s != ':' && !$this->isCorner($s) &&
                            $s != 'v') {
                            $dir = self::DIR_UP;
                        }
                        break;

                    /**
                     * Horizontal edges have the same properties for search as vertical
                     * edges, except we need to look east / west. The diagrams for the
                     * vertical case are still accurate to visualize this case; just
                     * mentally turn them 90 degrees clockwise.
                     */
                    case '=':
                        $line->setOption('stroke-dasharray', '5 5');
                    /* FALLTHROUGH */
                    case '-':
                        $w = $this->getChar($r, $c - 1);
                        $e = $this->getChar($r, $c + 1);
                        if (($w === '-' || $w === '=' || $this->isCorner($w)) &&
                            $e != '=' && $e != '-' && !$this->isCorner($e) &&
                            $e != '>') {
                            $dir = self::DIR_LEFT;
                        } elseif (($e === '-' || $e === '=' || $this->isCorner($e)) &&
                            $w != '=' && $w != '-' && !$this->isCorner($w) &&
                            $w != '<') {
                            $dir = self::DIR_RIGHT;
                        }
                        break;

                    /**
                     * We can only find diagonals going north or south and east. This is
                     * simplified due to the fact that they have no corners. We are
                     * guaranteed to run into their westernmost point or their relevant
                     * marker.
                     */
                    case '/':
                        $ne = $this->getChar($r - 1, $c + 1);
                        if ($ne === '/' || $ne === '^' || $ne === '>') {
                            $dir = self::DIR_NE;
                        }
                        break;

                    case "\\":
                        $se = $this->getChar($r + 1, $c + 1);
                        if ($se === "\\" || $se === "v" || $se === '>') {
                            $dir = self::DIR_SE;
                        }
                        break;

                    /**
                     * The corner case must consider all four directions. Though a
                     * reasonable person wouldn't use slant corners for this, they are
                     * considered corners, so it kind of makes sense to handle them the
                     * same way. For this case, envision the starting point being a corner
                     * character in both the horizontal and vertical case. And then
                     * mentally overlay them and consider that :).
                     */
                    case '+':
                    case '#':
                        $ne = $this->getChar($r - 1, $c + 1);
                        $se = $this->getChar($r + 1, $c + 1);
                        if ($ne === '/' || $ne === '^' || $ne === '>') {
                            $dir = self::DIR_NE;
                        } elseif ($se === "\\" || $se === "v" || $se === '>') {
                            $dir = self::DIR_SE;
                        }
                    /* FALLTHROUGH */

                    case '.':
                    case "'":
                        $n = $this->getChar($r - 1, $c);
                        $w = $this->getChar($r, $c - 1);
                        $s = $this->getChar($r + 1, $c);
                        $e = $this->getChar($r, $c + 1);
                        if (($w === '=' || $w === '-') && $n !== '|' && $n !== ':' && $w !== '-' &&
                            $e !== '=' && $e !== '|' && $s !== ':') {
                            $dir = self::DIR_LEFT;
                        } elseif (($e === '=' || $e === '-') && $n !== '|' && $n !== ':' &&
                            $w !== '-' && $w !== '=' && $s !== '|' && $s !== ':') {
                            $dir = self::DIR_RIGHT;
                        } elseif (($s === '|' || $s === ':') && $n !== '|' && $n !== ':' &&
                            $w !== '-' && $w !== '=' && $e !== '-' && $e !== '=' &&
                            (($char !== '.' && $char !== "'") ||
                                ($char === '.' && $s !== '.') ||
                                ($char === "'" && $s !== "'"))) {
                            $dir = self::DIR_DOWN;
                        } elseif (($n === '|' || $n === ':') && $s !== '|' && $s !== ':' &&
                            $w !== '-' && $w !== '=' && $e !== '-' && $e !== '=' &&
                            (($char !== '.' && $char !== "'") ||
                                ($char === '.' && $s !== '.') ||
                                ($char === "'" && $s !== "'"))) {
                            $dir = self::DIR_UP;
                        }
                        break;
                }

                /* It does actually save lines! */
                if ($dir !== false) {
                    $rInc = 0;
                    $cInc = 0;
                    if (!$this->isMarker($char)) {
                        $line->addPoint($c, $r);
                    }

                    /**
                     * The walk routine may attempt to add the point again, so skip it.
                     * If we don't, we can miss the line or end up with just a point.
                     */
                    if ($dir === self::DIR_UP) {
                        $rInc = -1;
                        $cInc = 0;
                    } elseif ($dir === self::DIR_DOWN) {
                        $rInc = 1;
                        $cInc = 0;
                    } elseif ($dir === self::DIR_RIGHT) {
                        $rInc = 0;
                        $cInc = 1;
                    } elseif ($dir === self::DIR_LEFT) {
                        $rInc = 0;
                        $cInc = -1;
                    } elseif ($dir === self::DIR_NE) {
                        $rInc = -1;
                        $cInc = 1;
                    } elseif ($dir === self::DIR_SE) {
                        $rInc = 1;
                        $cInc = 1;
                    }

                    /**
                     * Walk the points of this line. Note we don't use wallFollow; we are
                     * operating under the assumption that lines do not meander. (And, in
                     * any event, that algorithm is intended to find a closed object.)
                     */
                    $this->walk($line, $r + $rInc, $c + $cInc, $dir);

                    /**
                     * Remove it so that we don't confuse any other lines. This leaves
                     * corners in tact, still.
                     */
                    $this->clearObject($line);
                    $this->svgObjects->addObject($line);

                    /* We may be able to find more lines starting from this same point */
                    if ($this->isCorner($char)) {
                        $r--;
                    }
                }
            }
        }

        $this->svgObjects->popGroup();
    }

    /*
   * Look for text in a file. If the text appears in a box that has a dark
   * fill, we want to give it a light fill (and vice versa). This means we
   * have to figure out what box it lives in (if any) and do all sorts of
   * color calculation magic.
   */
    private function parseText(): void
    {
        $o = Scale::getInstance();

        /*
     * The style options deserve some comments. The monospace and font-size
     * choices are not accidental. This gives the best sort of estimation
     * for font size to scale that I could come up with empirically.
     *
     * N.B. This might change with different scales. I kind of feel like this
     * is a bug waiting to be filed, but whatever.
     */
        $fSize = 0.95 * $o->yScale;
        $this->svgObjects->pushGroup('text');
        $this->svgObjects->setOption('fill', 'black');
        $this->svgObjects->setOption('style',
            "font-family:{$this->fontFamily};font-size:{$fSize}px");

        /*
     * Text gets the same scanning treatment as boxes. We do left-to-right
     * scanning, which should probably be configurable in case someone wants
     * to use this with e.g. Arabic or some other right-to-left language.
     * Either way, this isn't UTF-8 safe (thanks, PHP!!!), so that'll require
     * thought regardless.
     */
        $boxes = $this->svgObjects->getGroup('boxes');
        $bound = count($boxes);

        foreach ($this->grid as $row => $line) {
            $cols = count($line);
            for ($i = 0; $i < $cols; $i++) {
                if ($this->getChar($row, $i) !== ' ') {
                    /* More magic numbers that probably need research. */
                    $t = new SVGText($i - .6, $row + 0.3);

                    /* Time to figure out which (if any) box we live inside */
                    $tP = $t->getPoint();

                    $maxPoint = new Point(-1, -1);
                    $boxQueue = array();

                    for ($j = 0; $j < $bound; $j++) {
                        if ($boxes[$j]->hasPoint($tP->gridX, $tP->gridY)) {
                            $boxPoints = $boxes[$j]->getPoints();
                            $boxTL = $boxPoints[0];

                            /**
                             * This text is in this box, but it may still be in a more
                             * specific nested box. Find the box with the highest top
                             * left X,Y coordinate. Keep a queue of boxes in case the top
                             * most box doesn't have a fill.
                             */
                            if ($boxTL->y > $maxPoint->y && $boxTL->x > $maxPoint->x) {
                                $maxPoint->x = $boxTL->x;
                                $maxPoint->y = $boxTL->y;
                                $boxQueue[] = $boxes[$j];
                            }
                        }
                    }

                    if (count($boxQueue) > 0) {
                        /**
                         * Work backwards through the boxes to find the box with the most
                         * specific fill.
                         */
                        for ($j = count($boxQueue) - 1; $j >= 0; $j--) {
                            $fill = $boxQueue[$j]->getOption('fill');

                            if ($fill === 'none' || $fill === null) {
                                continue;
                            }

                            if (substr($fill, 0, 1) !== '#') {
                                if (!isset($GLOBALS['colors'][strtolower($fill)])) {
                                    continue;
                                }
                                $fill = $GLOBALS['colors'][strtolower($fill)];
                            } else {
                                if (strlen($fill) != 4 && strlen($fill) != 7) {
                                    continue;
                                }
                            }

                            $cR = 0;
                            $cG = 0;
                            $cB = 0;

                            if ($fill) {
                                /* Attempt to parse the fill color */
                                if (strlen($fill) === 4) {
                                    $cR = hexdec(str_repeat($fill[1], 2));
                                    $cG = hexdec(str_repeat($fill[2], 2));
                                    $cB = hexdec(str_repeat($fill[3], 2));
                                } elseif (strlen($fill) === 7) {
                                    $cR = hexdec(substr($fill, 1, 2));
                                    $cG = hexdec(substr($fill, 3, 2));
                                    $cB = hexdec(substr($fill, 5, 2));
                                }

                                /**
                                 * This magic is gleaned from the working group paper on
                                 * accessibility at http://www.w3.org/TR/AERT. The recommended
                                 * contrast is a brightness difference of at least 125 and a
                                 * color difference of at least 500. Since our default color
                                 * is black, that makes the color difference easier.
                                 */
                                $bFill = (($cR * 299) + ($cG * 587) + ($cB * 114)) / 1000;
                                $bDiff = $cR + $cG + $cB;
                                $bText = 0;

                                if ($bFill - $bText < 125 || $bDiff < 500) {
                                    /* If black is too dark, white will work */
                                    $t->setOption('fill', '#fff');
                                } else {
                                    $t->setOption('fill', '#000');
                                }

                                break;
                            }
                        }

                        if ($j < 0) {
                            $t->setOption('fill', '#000');
                        }
                    } else {
                        /* This text isn't inside a box; make it black */
                        $t->setOption('fill', '#000');
                    }

                    /* We found a stringy character, eat it and the rest. */
                    $str = $this->getChar($row, $i++);
                    while ($i < count($line) && $this->getChar($row, $i) !== ' ') {
                        $str .= $this->getChar($row, $i++);
                        /* Eat up to 1 space */
                        if ($this->getChar($row, $i) === ' ') {
                            $str .= ' ';
                            $i++;
                        }
                    }

                    if ($str === '') {
                        continue;
                    }

                    $t->setString($str);

                    /**
                     * If we were in a box, group with the box. Otherwise it gets its
                     * own group.
                     */
                    if (count($boxQueue) > 0) {
                        $t->setOption('stroke', 'none');
                        $t->setOption('style',
                            "font-family:{$this->fontFamily};font-size:{$fSize}px");
                        $boxQueue[count($boxQueue) - 1]->addText($t);
                    } else {
                        $this->svgObjects->addObject($t);
                    }
                }
            }
        }
    }

    /**
     * Allow specifying references that target an object starting at grid point
     * (ROW,COL). This allows styling of lines, boxes, or any text object.
     */
    private function injectCommands(): void
    {
        $boxes = $this->svgObjects->getGroup('boxes');
        $lines = $this->svgObjects->getGroup('lines');
        $text = $this->svgObjects->getGroup('text');

        foreach ($boxes as $obj) {
            $objPoints = $obj->getPoints();
            $pointCmd = "{$objPoints[0]->gridY},{$objPoints[0]->gridX}";

            if (isset($this->commands[$pointCmd])) {
                $obj->setOptions($this->commands[$pointCmd]);
            }

            foreach ($obj->getText() as $text) {
                $textPoint = $text->getPoint();
                $pointCmd = "{$textPoint->gridY},{$textPoint->gridX}";

                if (isset($this->commands[$pointCmd])) {
                    $text->setOptions($this->commands[$pointCmd]);
                }
            }
        }

        foreach ($lines as $obj) {
            $objPoints = $obj->getPoints();
            $pointCmd = "{$objPoints[0]->gridY},{$objPoints[0]->gridX}";

            if (isset($this->commands[$pointCmd])) {
                $obj->setOptions($this->commands[$pointCmd]);
            }
        }

        foreach ($text as $obj) {
            $objPoint = $obj->getPoint();
            $pointCmd = "{$objPoint->gridY},{$objPoint->gridX}";

            if (isset($this->commands[$pointCmd])) {
                $obj->setOptions($this->commands[$pointCmd]);
            }
        }
    }

    /**
     * A generic, recursive line walker. This walker makes the assumption that
     * lines want to go in the direction that they are already heading. I'm
     * sure that there are ways to formulate lines to screw this walker up,
     * but it does a good enough job right now.
     *
     * @return null
     */
    private function walk(SVGPath $path, int $row, int $col, int $dir, int $d = 0)
    {
        $d++;
        $r = $row;
        $c = $col;
        $cInc = 0;
        $rInc = 0;

        if ($dir === self::DIR_RIGHT || $dir === self::DIR_LEFT) {
            $cInc = ($dir === self::DIR_RIGHT) ? 1 : -1;
            $rInc = 0;
        } elseif ($dir === self::DIR_DOWN || $dir === self::DIR_UP) {
            $cInc = 0;
            $rInc = ($dir === self::DIR_DOWN) ? 1 : -1;
        } elseif ($dir === self::DIR_SE || $dir === self::DIR_NE) {
            $cInc = 1;
            $rInc = ($dir === self::DIR_SE) ? 1 : -1;
        }

        /* Follow the edge for as long as we can */
        $cur = $this->getChar($r, $c);
        while ($this->isEdge($cur, $dir)) {
            if ($cur === ':' || $cur === '=') {
                $path->setOption('stroke-dasharray', '5 5');
            }

            if ($this->isTick($cur)) {
                $path->addTick($c, $r, ($cur === 'o') ? Point::DOT : Point::TICK);
                $path->addPoint($c, $r);
            }

            $c += $cInc;
            $r += $rInc;
            $cur = $this->getChar($r, $c);
        }

        if ($this->isCorner($cur)) {
            if ($cur === '.' || $cur === "'") {
                $path->addPoint($c, $r, Point::CONTROL);
            } else {
                $path->addPoint($c, $r);
            }

            if ($path->isClosed()) {
                $path->popPoint();
                return null;
            }

            /**
             * Attempt first to continue in the current direction. If we can't,
             * try to go in any direction other than the one opposite of where
             * we just came from -- no backtracking.
             */
            $n = $this->getChar($r - 1, $c);
            $s = $this->getChar($r + 1, $c);
            $e = $this->getChar($r, $c + 1);
            $w = $this->getChar($r, $c - 1);
            $next = $this->getChar($r + $rInc, $c + $cInc);

            $se = $this->getChar($r + 1, $c + 1);
            $ne = $this->getChar($r - 1, $c + 1);

            if ($this->isCorner($next) || $this->isEdge($next, $dir)) {
                return $this->walk($path, $r + $rInc, $c + $cInc, $dir, $d);
            }
            if ($dir !== self::DIR_DOWN &&
                ($this->isCorner($n) || $this->isEdge($n, self::DIR_UP))) {
                /* Can't turn up into bottom corner */
                if (($cur !== '.' && $cur !== "'") || ($cur === '.' && $n !== '.') ||
                    ($cur === "'" && $n !== "'")) {
                    return $this->walk($path, $r - 1, $c, self::DIR_UP, $d);
                }
            } elseif ($dir !== self::DIR_UP &&
                ($this->isCorner($s) || $this->isEdge($s, self::DIR_DOWN))) {
                /* Can't turn down into top corner */
                if (($cur !== '.' && $cur !== "'") || ($cur === '.' && $s !== '.') ||
                    ($cur === "'" && $s !== "'")) {
                    return $this->walk($path, $r + 1, $c, self::DIR_DOWN, $d);
                }
            } elseif ($dir !== self::DIR_LEFT &&
                ($this->isCorner($e) || $this->isEdge($e, self::DIR_RIGHT))) {
                return $this->walk($path, $r, $c + 1, self::DIR_RIGHT, $d);
            } elseif ($dir !== self::DIR_RIGHT &&
                ($this->isCorner($w) || $this->isEdge($w, self::DIR_LEFT))) {
                return $this->walk($path, $r, $c - 1, self::DIR_LEFT, $d);
            } elseif ($dir === self::DIR_SE &&
                ($this->isCorner($ne) || $this->isEdge($ne, self::DIR_NE))) {
                return $this->walk($path, $r - 1, $c + 1, self::DIR_NE, $d);
            } elseif ($dir === self::DIR_NE &&
                ($this->isCorner($se) || $this->isEdge($se, self::DIR_SE))) {
                return $this->walk($path, $r + 1, $c + 1, self::DIR_SE, $d);
            }
        } elseif ($this->isMarker($cur)) {
            /* We found a marker! Add it. */
            $path->addMarker($c, $r, Point::SMARKER);
            return null;
        } else {
            /*
       * Not a corner, not a marker, and we already ate edges. Whatever this
       * is, it is not part of the line.
       */
            $path->addPoint($c - $cInc, $r - $rInc);
            return null;
        }
        return null;
    }

    /**
     * This function attempts to follow a line and complete it into a closed
     * polygon. It assumes that we have been called from a top point, and in any
     * case that the polygon can be found by moving clockwise along its edges.
     * Any time this algorithm finds a corner, it attempts to turn right. If it
     * cannot turn right, it goes in any direction other than the one it came
     * from. If it cannot complete the polygon by continuing in any direction
     * from a point, that point is removed from the path, and we continue on
     * from the previous point (since this is a recursive function).
     *
     * Because the function assumes that it is starting from the top left,
     * if its first turn cannot be a right turn to moving down, the object
     * cannot be a valid polygon. It also maintains an internal list of points
     * it has already visited, and refuses to visit any point twice.
     *
     * @return null
     */
    private function wallFollow(SVGPath $path, int $r, int $c, int $dir, array $bucket = [], int $d = 0)
    {
        $d++;
        $rInc = 0;
        $cInc = 0;

        if ($dir === self::DIR_RIGHT || $dir === self::DIR_LEFT) {
            $cInc = ($dir === self::DIR_RIGHT) ? 1 : -1;
        } elseif ($dir === self::DIR_DOWN || $dir === self::DIR_UP) {
            $rInc = ($dir === self::DIR_DOWN) ? 1 : -1;
        }

        /* Traverse the edge in whatever direction we are going. */
        $cur = $this->getChar($r, $c);
        while ($this->isBoxEdge($cur, $dir)) {
            $r += $rInc;
            $c += $cInc;
            $cur = $this->getChar($r, $c);
        }

        /* We 'key' our location by catting r and c together */
        $key = "{$r}{$c}";
        if (isset($bucket[$key])) {
            return null;
        }

        /**
         * When we run into a corner, we have to make a somewhat complicated
         * decision about which direction to turn.
         */
        if ($this->isBoxCorner($cur)) {
            if (!isset($bucket[$key])) {
                $bucket[$key] = 0;
            }

            $pointExists = null;
            switch ($cur) {
                case '.':
                case "'":
                    $pointExists = $path->addPoint($c, $r, Point::CONTROL);
                    break;

                case '#':
                    $pointExists = $path->addPoint($c, $r);
                    break;
            }

            if ($path->isClosed() || $pointExists) {
                return null;
            }

            /**
             * Special case: if we're looking for our first turn and we can't make it
             * due to incompatible corners, keep looking, but don't adjust our call
             * depth so that we can continue to make progress.
             */
            if ($d === 1 && $cur === '.' && $this->getChar($r + 1, $c) === '.') {
                return $this->wallFollow($path, $r, $c + 1, $dir, $bucket, 0);
            }

            /**
             * We need to make a decision here on where to turn. We may have multiple
             * directions we can choose, and all of them might generate a closed
             * object. Always try turning right first.
             */
            $newDir = false;
            $n = $this->getChar($r - 1, $c);
            $s = $this->getChar($r + 1, $c);
            $e = $this->getChar($r, $c + 1);
            $w = $this->getChar($r, $c - 1);

            if ($dir === self::DIR_RIGHT) {
                if (!($bucket[$key] & self::DIR_DOWN) &&
                    ($this->isBoxEdge($s, self::DIR_DOWN) || $this->isBoxCorner($s))) {
                    /* We can't turn into another top edge. */
                    if (($cur !== '.' && $cur !== "'") || ($cur === '.' && $s !== '.') ||
                        ($cur === "'" && $s !== "'")) {
                        $newDir = self::DIR_DOWN;
                    }
                } else {
                    /* There is no right hand turn for us; this isn't a valid start */
                    if ($d === 1) {
                        return null;
                    }
                }
            } elseif ($dir === self::DIR_DOWN) {
                if (!($bucket[$key] & self::DIR_LEFT) &&
                    ($this->isBoxEdge($w, self::DIR_LEFT) || $this->isBoxCorner($w))) {
                    $newDir = self::DIR_LEFT;
                }
            } elseif ($dir === self::DIR_LEFT) {
                if (!($bucket[$key] & self::DIR_UP) &&
                    ($this->isBoxEdge($n, self::DIR_UP) || $this->isBoxCorner($n))) {
                    /* We can't turn into another bottom edge. */
                    if (($cur !== '.' && $cur !== "'") || ($cur === '.' && $n !== '.') ||
                        ($cur === "'" && $n !== "'")) {
                        $newDir = self::DIR_UP;
                    }
                }
            } elseif ($dir === self::DIR_UP) {
                if (!($bucket[$key] & self::DIR_RIGHT) &&
                    ($this->isBoxEdge($e, self::DIR_RIGHT) || $this->isBoxCorner($e))) {
                    $newDir = self::DIR_RIGHT;
                }
            }

            $cMod = 0;
            $rMod = 0;
            if ($newDir !== false) {
                if ($newDir === self::DIR_RIGHT || $newDir === self::DIR_LEFT) {
                    $cMod = ($newDir === self::DIR_RIGHT) ? 1 : -1;
                    $rMod = 0;
                } elseif ($newDir === self::DIR_DOWN || $newDir === self::DIR_UP) {
                    $cMod = 0;
                    $rMod = ($newDir === self::DIR_DOWN) ? 1 : -1;
                }

                $bucket[$key] |= $newDir;
                $this->wallFollow($path, $r + $rMod, $c + $cMod, $newDir, $bucket, $d);
                /** @phpstan-ignore-next-line */
                if ($path->isClosed()) {
                    return null;
                }
            }

            /**
             * Unfortunately, we couldn't complete the search by turning right,
             * so we need to pick a different direction. Note that this will also
             * eventually cause us to continue in the direction we were already
             * going. We make sure that we don't go in the direction opposite of
             * the one in which we're already headed, or an any direction we've
             * already travelled for this point (we may have hit it from an
             * earlier branch). We accept the first closing polygon as the
             * "correct" one for this object.
             */
            if ($dir != self::DIR_RIGHT && !($bucket[$key] & self::DIR_LEFT) &&
                ($this->isBoxEdge($w, self::DIR_LEFT) || $this->isBoxCorner($w))) {
                $bucket[$key] |= self::DIR_LEFT;
                $this->wallFollow($path, $r, $c - 1, self::DIR_LEFT, $bucket, $d);
                /** @phpstan-ignore-next-line */
                if ($path->isClosed()) {
                    return null;
                }
            }
            if ($dir != self::DIR_LEFT && !($bucket[$key] & self::DIR_RIGHT) &&
                ($this->isBoxEdge($e, self::DIR_RIGHT) || $this->isBoxCorner($e))) {
                $bucket[$key] |= self::DIR_RIGHT;
                $this->wallFollow($path, $r, $c + 1, self::DIR_RIGHT, $bucket, $d);
                /** @phpstan-ignore-next-line */
                if ($path->isClosed()) {
                    return null;
                }
            }
            if ($dir != self::DIR_DOWN && !($bucket[$key] & self::DIR_UP) &&
                ($this->isBoxEdge($n, self::DIR_UP) || $this->isBoxCorner($n))) {
                if (($cur !== '.' && $cur !== "'") || ($cur === '.' && $n !== '.') ||
                    ($cur === "'" && $n !== "'")) {
                    /* We can't turn into another bottom edge. */
                    $bucket[$key] |= self::DIR_UP;
                    $this->wallFollow($path, $r - 1, $c, self::DIR_UP, $bucket, $d);
                    /** @phpstan-ignore-next-line */
                    if ($path->isClosed()) {
                        return null;
                    }
                }
            }
            if ($dir != self::DIR_UP && !($bucket[$key] & self::DIR_DOWN) &&
                ($this->isBoxEdge($s, self::DIR_DOWN) || $this->isBoxCorner($s))) {
                if (($cur !== '.' && $cur !== "'") || ($cur === '.' && $s !== '.') ||
                    ($cur === "'" && $s !== "'")) {
                    /* We can't turn into another top edge. */
                    $bucket[$key] |= self::DIR_DOWN;
                    $this->wallFollow($path, $r + 1, $c, self::DIR_DOWN, $bucket, $d);
                    /** @phpstan-ignore-next-line */
                    if ($path->isClosed()) {
                        return null;
                    }
                }
            }

            /**
             * If we get here, the path doesn't close in any direction from this
             * point (it's probably a line extension). Get rid of the point from our
             * path and go back to the last one.
             */
            $path->popPoint();
            return null;
        }
        if ($this->isMarker($this->getChar($r, $c))) {
            /* Marker is part of a line, not a wall to close. */
            return null;
        }
        /* We landed on some whitespace or something; this isn't a closed path */
        return null;
    }

    /*
   * Clears an object from the grid, erasing all edge and marker points. This
   * function retains corners in "clearCorners" to be cleaned up before we do
   * text parsing.
   */
    private function clearObject(SVGPath $obj): void
    {
        $points = $obj->getPoints();
        $closed = $obj->isClosed();

        $bound = count($points);
        for ($i = 0; $i < $bound; $i++) {
            $p = $points[$i];

            if ($i === count($points) - 1) {
                /* This keeps us from handling end of line to start of line */
                if ($closed) {
                    $nP = $points[0];
                } else {
                    $nP = null;
                }
            } else {
                $nP = $points[$i + 1];
            }

            /* If we're on the same vertical axis as our next point... */
            if ($nP != null && $p->gridX === $nP->gridX) {
                /* ...traverse the vertical line from the minimum to maximum points */
                $maxY = max($p->gridY, $nP->gridY);
                /** @var float $j */
                for ($j = min($p->gridY, $nP->gridY); $j <= $maxY; $j++) {
                    $char = $this->getChar($j, $p->gridX);

                    if (!$this->isTick($char) && $this->isEdge($char) || $this->isMarker($char)) {
                        $this->grid[$j][$p->gridX] = ' ';
                    } elseif ($this->isCorner($char)) {
                        $this->clearCorners[] = array($j, $p->gridX);
                    } elseif ($this->isTick($char)) {
                        $this->grid[$j][$p->gridX] = '+';
                    }
                }
            } elseif ($nP != null && $p->gridY === $nP->gridY) {
                /* Same horizontal plane; traverse from min to max point */
                $maxX = max($p->gridX, $nP->gridX);
                for ($j = min($p->gridX, $nP->gridX); $j <= $maxX; $j++) {
                    $char = $this->getChar($p->gridY, $j);

                    if (!$this->isTick($char) && $this->isEdge($char) || $this->isMarker($char)) {
                        $this->grid[$p->gridY][$j] = ' ';
                    } elseif ($this->isCorner($char)) {
                        $this->clearCorners[] = array($p->gridY, $j);
                    } elseif ($this->isTick($char)) {
                        $this->grid[$p->gridY][$j] = '+';
                    }
                }
            } elseif ($nP != null && $closed === false && $p->gridX != $nP->gridX &&
                $p->gridY != $nP->gridY) {
                /**
                 * This is a diagonal line starting from the westernmost point. It
                 * must contain max(p->gridY, nP->gridY) - min(p->gridY, nP->gridY)
                 * segments, and we can tell whether to go north or south depending
                 * on which side of zero p->gridY - nP->gridY lies. There are no
                 * corners in diagonals, so we don't have to keep those around.
                 */
                $c = $p->gridX;
                $r = $p->gridY;
                $rInc = ($p->gridY > $nP->gridY) ? -1 : 1;
                $bound = max($p->gridY, $nP->gridY) - min($p->gridY, $nP->gridY);

                /**
                 * This looks like an off-by-one, but it is not. This clears the
                 * corner, if one exists.
                 */
                for ($j = 0; $j <= $bound; $j++) {
                    $char = $this->getChar($r, $c);
                    if ($char === '/' || $char === "\\" || $this->isMarker($char)) {
                        $this->grid[$r][$c++] = ' ';
                    } elseif ($this->isCorner($char)) {
                        $this->clearCorners[] = array($r, $c++);
                    } elseif ($this->isTick($char)) {
                        $this->grid[$r][$c] = '+';
                    }
                    $r += $rInc;
                }

                $this->grid[$p->gridY][$p->gridX] = ' ';
                break;
            }
        }
    }

    /**
     * Find style information for this polygon. This information is required to
     * exist on the first line after the top, touching the left wall. It's kind
     * of a pain requirement, but there's not a much better way to do it:
     * ditaa's handling requires too much text flung everywhere and this way
     * gives you a good method for specifying *tons* of information about the
     * object.
     */
    private function findCommands(SVGPath $box): string
    {
        $points = $box->getPoints();
        $sX = $points[0]->gridX + 1;
        $sY = $points[0]->gridY + 1;
        $ref = '';
        if ($this->getChar($sY, $sX++) === '[') {
            $char = $this->getChar($sY, $sX++);
            while ($char !== ']') {
                $ref .= $char;
                $char = $this->getChar($sY, $sX++);
            }

            if ($char === ']') {
                $sX = $points[0]->gridX + 1;
                $sY = $points[0]->gridY + 1;

                if (!isset($this->commands[$ref]['a2s:delref']) &&
                    !isset($this->commands[$ref]['a2s:label'])) {
                    $this->grid[$sY][$sX] = ' ';
                    $this->grid[$sY][$sX + strlen($ref) + 1] = ' ';
                } else {
                    if (isset($this->commands[$ref]['a2s:label'])) {
                        $label = $this->commands[$ref]['a2s:label'];
                    } else {
                        $label = ''; //TODO - was null
                    }

                    $len = strlen($ref) + 2;
                    for ($i = 0; $i < $len; $i++) {
                        if (strlen($label) > $i) {
                            $this->grid[$sY][$sX + $i] = substr($label, $i, 1);
                        } else {
                            $this->grid[$sY][$sX + $i] = ' ';
                        }
                    }
                }

                if (isset($this->commands[$ref])) {
                    $box->setOptions($this->commands[$ref]);
                }
            }
        }

        return $ref;
    }

    /**
     * Extremely useful debugging information to figure out what has been
     * parsed, especially when used in conjunction with clearObject.
     */
    public function dumpGrid(): void
    {
        foreach ($this->grid as $lines) {
            echo implode('', $lines) . "\n";
        }
    }

    private function getChar(int|float $row, int|float $col): ?string
    {
        $row = (int)$row;
        $col = (int)$col;

        if (isset($this->grid[$row][$col])) {
            return $this->grid[$row][$col];
        }

        return null;
    }

    private function isBoxEdge(?string $char, ?int $dir = null): bool
    {
        if ($dir === null) {
            return $char === '-' || $char === '|' || $char === ':' || $char === '=' || $char === '*' || $char === '+';
        }
        if ($dir === self::DIR_UP || $dir === self::DIR_DOWN) {
            return $char === '|' || $char === ':' || $char === '*' || $char === '+';
        }
        if ($dir === self::DIR_LEFT || $dir === self::DIR_RIGHT) {
            return $char === '-' || $char === '=' || $char === '*' || $char === '+';
        }
        return false;
    }

    private function isEdge(?string $char, ?int $dir = null): bool
    {
        if ($char === 'o' || $char === 'x') {
            return true;
        }

        if ($dir === null) {
            return $char === '-' || $char === '|' || $char === ':' || $char === '=' || $char === '*' || $char === '/' || $char === "\\";
        }
        if ($dir === self::DIR_UP || $dir === self::DIR_DOWN) {
            return $char === '|' || $char === ':' || $char === '*';
        }
        if ($dir === self::DIR_LEFT || $dir === self::DIR_RIGHT) {
            return $char === '-' || $char === '=' || $char === '*';
        }
        if ($dir === self::DIR_NE) {
            return $char === '/';
        }
        if ($dir === self::DIR_SE) {
            return $char === "\\";
        }
        return false;
    }

    private function isBoxCorner(?string $char): bool
    {
        return $char === '.' || $char === "'" || $char === '#';
    }

    private function isCorner(?string $char): bool
    {
        return $char === '.' || $char === "'" || $char === '#' || $char === '+';
    }

    private function isMarker(?string $char): bool
    {
        return $char === 'v' || $char === '^' || $char === '<' || $char === '>';
    }

    private function isTick(?string $char): bool
    {
        return $char === 'o' || $char === 'x';
    }
}
