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

/**
 * The Path class represents lines and polygons.
 */
class SVGPath
{
    /** @var array<string, string> */
    private ?array $options;

    /** @var array<Point>|null  */
    private ?array $points;
    private ?array $ticks;
    private ?int $flags;

    /** @var array<SVGText>|null */
    private ?array $text;
    private ?int $name;

    private static int $id = 0;

    const CLOSED = 0x1;

    public function __construct()
    {
        $this->options = [];
        $this->points = [];
        $this->text = [];
        $this->ticks = [];
        $this->flags = 0;
        $this->name = self::$id++;
    }

    /*
   * Making sure that we always started at the top left coordinate
   * makes so many things so much easier. First, find the lowest Y
   * position. Then, of all matching Y positions, find the lowest X
   * position. This is the top left.
   *
   * As far as the points are considered, they're definitely on the
   * top somewhere, but not necessarily the most left. This could
   * happen if there was a corner connector in the top edge (perhaps
   * for a line to connect to). Since we couldn't turn right there,
   * we have to try now.
   *
   * This should only be called when we close a polygon.
   */
    public function orderPoints(): void
    {
        $pPoints = count($this->points);

        $minY = $this->points[0]->y;
        $minX = $this->points[0]->x;
        $minIdx = 0;
        for ($i = 1; $i < $pPoints; $i++) {
            if ($this->points[$i]->y <= $minY) {
                $minY = $this->points[$i]->y;

                if ($this->points[$i]->x < $minX) {
                    $minX = $this->points[$i]->x;
                    $minIdx = $i;
                }
            }
        }

        /**
         * If our top left isn't at the 0th index, it is at the end. If
         * there are bits after it, we need to cut those and put them at
         * the front.
         */
        if ($minIdx !== 0) {
            $startPoints = array_splice($this->points, $minIdx);
            $this->points = array_merge($startPoints, $this->points);
        }
    }

    /**
     * Useful for recursive walkers when speculatively trying a direction.
     */
    public function popPoint(): void
    {
        array_pop($this->points);
    }

    public function addPoint(int $x, int $y, int $flags = Point::POINT): bool
    {
        $p = new Point($x, $y);

        /*
     * If we attempt to add our original point back to the path, the polygon
     * must be closed.
     */
        if (count($this->points) > 0) {
            if ($this->points[0]->x === $p->x && $this->points[0]->y === $p->y) {
                $this->flags |= self::CLOSED;
                return true;
            }

            /**
             * For the purposes of this library, paths should never intersect each
             * other. Even in the case of closing the polygon, we do not store the
             * final coordinate twice.
             */
            foreach ($this->points as $point) {
                if ($point->x === $p->x && $point->y === $p->y) {
                    return true;
                }
            }
        }

        $p->flags |= $flags;
        $this->points[] = $p;

        return false;
    }

    /**
     * It's useful to be able to know the points in a shape.
     */
    public function getPoints(): ?array
    {
        return $this->points;
    }

    /**
     * Add a marker to a line. The third argument specifies which marker to use,
     * and this depends on the orientation of the line. Due to the way the line
     * parser works, we may have to use an inverted representation.
     */
    public function addMarker(int $x, int $y, int $t): void
    {
        $p = new Point($x, $y);
        $p->flags |= $t;
        $this->points[] = $p;
    }

    public function addTick(int $x, int $y, int $t): void
    {
        $p = new Point($x, $y);
        $p->flags |= $t;
        $this->ticks[] = $p;
    }

    /**
     * Is this path closed?
     */
    public function isClosed(): bool
    {
        return (bool)($this->flags & self::CLOSED);
    }

    public function addText(SVGText $t): void
    {
        $this->text[] = $t;
    }

    public function getText(): array
    {
        return $this->text;
    }

    public function getID(): int
    {
        return $this->name;
    }

    /*
   * Set options as a JSON string. Specified as a merge operation so that it
   * can be called after an individual setOption call.
   */
    public function setOptions(array $opt): void
    {
        $this->options = array_merge($this->options, $opt);
    }

    public function setOption(string $opt, string $val): void
    {
        $this->options[$opt] = $val;
    }

    /**
     * @param string $opt
     * @return array|mixed|string|null
     */
    public function getOption(string $opt)
    {
        if (isset($this->options[$opt])) {
            return $this->options[$opt];
        }

        return null;
    }

    /**
     * Does the given point exist within this polygon? Since we can
     * theoretically have some complex concave and convex polygon edges in the
     * same shape, we need to do a full point-in-polygon test. This algorithm
     * seems like the standard one. See: http://alienryderflex.com/polygon/
     */
    public function hasPoint(int $x, int $y): bool
    {
        if ($this->isClosed() === false) {
            return false;
        }

        $oddNodes = false;

        $bound = count($this->points);
        for ($i = 0, $j = count($this->points) - 1; $i < $bound; $i++) {
            if (($this->points[$i]->gridY < $y && $this->points[$j]->gridY >= $y ||
                    $this->points[$j]->gridY < $y && $this->points[$i]->gridY >= $y) &&
                ($this->points[$i]->gridX <= $x || $this->points[$j]->gridX <= $x)) {
                if ($this->points[$i]->gridX + ($y - $this->points[$i]->gridY) /
                    ($this->points[$j]->gridY - $this->points[$i]->gridY) *
                    ($this->points[$j]->gridX - $this->points[$i]->gridX) < $x) {
                    $oddNodes = !$oddNodes;
                }
            }

            $j = $i;
        }

        return $oddNodes;
    }

    /**
     * Apply a matrix transformation to the coordinates ($x, $y). The
     * multiplication is implemented on the matrices:
     *
     * | a b c |   | x |
     * | d e f | * | y |
     * | 0 0 1 |   | 1 |
     *
     * Additional information on the transformations and what each R,C in the
     * transformation matrix represents, see:
     *
     * http://www.w3.org/TR/SVG/coords.html#TransformMatrixDefined
     */
    private function matrixTransform(array $matrix, int $x, int $y): array
    {
        $xyMat = array(array($x), array($y), array(1));
        $newXY = array(array());

        for ($i = 0; $i < 3; $i++) {
            for ($j = 0; $j < 1; $j++) {
                $sum = 0;

                for ($k = 0; $k < 3; $k++) {
                    $sum += $matrix[$i][$k] * $xyMat[$k][$j];
                }

                $newXY[$i][$j] = $sum;
            }
        }

        /* Return the coordinates as a vector */
        return array($newXY[0][0], $newXY[1][0], $newXY[2][0]);
    }

    /**
     * Translate the X and Y coordinates. tX and tY specify the distance to
     * transform.
     */
    private function translateTransform(int $tX, int $tY, int $x, int $y): array
    {
        $matrix = array(array(1, 0, $tX), array(0, 1, $tY), array(0, 0, 1));
        return $this->matrixTransform($matrix, $x, $y);
    }

    /**
     * Scale transformations are implemented by applying the scale to the X and
     * Y coordinates. One unit in the new coordinate system equals $s[XY] units
     * in the old system. Thus, if you want to double the size of an object on
     * both axes, you sould call scaleTransform(0.5, 0.5, $x, $y)
     */
    private function scaleTransform(int $sX, int $sY, int $x, int $y): array
    {
        $matrix = array(array($sX, 0, 0), array(0, $sY, 0), array(0, 0, 1));
        return $this->matrixTransform($matrix, $x, $y);
    }

    /*
   * Rotate the coordinates around the center point cX and cY. If these
   * are not specified, the coordinate is rotated around 0,0. The angle
   * is specified in degrees.
   */
    private function rotateTransform(float $angle, int $x, int $y, int $cX = 0, int $cY = 0): array
    {
        $angle *= (M_PI / 180);
        if ($cX !== 0 || $cY !== 0) {
            [$x, $y] = $this->translateTransform($cX, $cY, $x, $y);
        }

        $matrix = array(array(cos($angle), -sin($angle), 0),
            array(sin($angle), cos($angle), 0),
            array(0, 0, 1));
        $ret = $this->matrixTransform($matrix, $x, $y);

        if ($cX !== 0 || $cY !== 0) {
            [$x, $y] = $this->translateTransform(-$cX, -$cY, $ret[0], $ret[1]);
            $ret[0] = $x;
            $ret[1] = $y;
        }

        return $ret;
    }

    /**
     * Skews along the X axis at specified angle. The angle is specified in
     * degrees.
     */
    private function skewXTransform(float $angle, int $x, int $y): array
    {
        $angle *= (M_PI / 180);
        $matrix = array(array(1, tan($angle), 0), array(0, 1, 0), array(0, 0, 1));
        return $this->matrixTransform($matrix, $x, $y);
    }

    /**
     * Skews along the Y axis at specified angle. The angle is specified in
     * degrees.
     */
    private function skewYTransform(float $angle, int $x, int $y): array
    {
        $angle *= (M_PI / 180);
        $matrix = array(array(1, 0, 0), array(tan($angle), 1, 0), array(0, 0, 1));
        return $this->matrixTransform($matrix, $x, $y);
    }

    /**
     * Apply a transformation to a point $p.
     */
    private function applyTransformToPoint(string $txf, Point $p, array $args): array
    {
        switch ($txf) {
            case 'translate':
                return $this->translateTransform($args[0], $args[1], $p->x, $p->y);

            case 'scale':
                return $this->scaleTransform($args[0], $args[1], $p->x, $p->y);

            case 'rotate':
                if (count($args) > 1) {
                    return $this->rotateTransform($args[0], $p->x, $p->y, $args[1], $args[2]);
                }
                return $this->rotateTransform($args[0], $p->x, $p->y);

            case 'skewX':
                return $this->skewXTransform($args[0], $p->x, $p->y);

            case 'skewY':
                return $this->skewYTransform($args[0], $p->x, $p->y);
        }

        throw new \RuntimeException('Invalid transform');
    }

    /*
   * Apply the transformation function $txf to all coordinates on path $p
   * providing $args as arguments to the transformation function.
   */
    private function applyTransformToPath(string $txf, array &$p, array $args): void
    {
        $pathCmds = count($p['path']);
        $curPoint = new Point(0, 0);
        $prevType = null;
        $curType = null;

        for ($i = 0; $i < $pathCmds; $i++) {
            $cmd = &$p['path'][$i];

            $prevType = $curType;
            $curType = $cmd[0];

            switch ($curType) {
                case 'z':
                case 'Z':
                    /* Can't transform this */
                    break;

                case 'm':
                    if ($prevType != null) {
                        $curPoint->x += $cmd[1];
                        $curPoint->y += $cmd[2];

                        list ($x, $y) = $this->applyTransformToPoint($txf, $curPoint, $args);
                        $curPoint->x = $x;
                        $curPoint->y = $y;

                        $cmd[1] = $x;
                        $cmd[2] = $y;
                    } else {
                        $curPoint->x = $cmd[1];
                        $curPoint->y = $cmd[2];

                        list ($x, $y) = $this->applyTransformToPoint($txf, $curPoint, $args);
                        $curPoint->x = $x;
                        $curPoint->y = $y;

                        $cmd[1] = $x;
                        $cmd[2] = $y;
                        $curType = 'l';
                    }

                    break;

                case 'M':
                    $curPoint->x = $cmd[1];
                    $curPoint->y = $cmd[2];

                    list ($x, $y) = $this->applyTransformToPoint($txf, $curPoint, $args);
                    $curPoint->x = $x;
                    $curPoint->y = $y;

                    $cmd[1] = $x;
                    $cmd[2] = $y;

                    if ($prevType == null) {
                        $curType = 'L';
                    }
                    break;

                case 'l':
                    $curPoint->x += $cmd[1];
                    $curPoint->y += $cmd[2];

                    list ($x, $y) = $this->applyTransformToPoint($txf, $curPoint, $args);
                    $curPoint->x = $x;
                    $curPoint->y = $y;

                    $cmd[1] = $x;
                    $cmd[2] = $y;

                    break;

                case 'L':
                    $curPoint->x = $cmd[1];
                    $curPoint->y = $cmd[2];

                    list ($x, $y) = $this->applyTransformToPoint($txf, $curPoint, $args);
                    $curPoint->x = $x;
                    $curPoint->y = $y;

                    $cmd[1] = $x;
                    $cmd[2] = $y;

                    break;

                case 'v':
                    $curPoint->y += $cmd[1];
                    $curPoint->x += 0;

                    list ($x, $y) = $this->applyTransformToPoint($txf, $curPoint, $args);
                    $curPoint->x = $x;
                    $curPoint->y = $y;

                    $cmd[1] = $y;

                    break;

                case 'V':
                    $curPoint->y = $cmd[1];

                    list ($x, $y) = $this->applyTransformToPoint($txf, $curPoint, $args);
                    $curPoint->x = $x;
                    $curPoint->y = $y;

                    $cmd[1] = $y;

                    break;

                case 'h':
                    $curPoint->x += $cmd[1];

                    list ($x, $y) = $this->applyTransformToPoint($txf, $curPoint, $args);
                    $curPoint->x = $x;
                    $curPoint->y = $y;

                    $cmd[1] = $x;

                    break;

                case 'H':
                    $curPoint->x = $cmd[1];

                    list ($x, $y) = $this->applyTransformToPoint($txf, $curPoint, $args);
                    $curPoint->x = $x;
                    $curPoint->y = $y;

                    $cmd[1] = $x;

                    break;

                case 'c':
                    $tP = new Point(0, 0);
                    $tP->x = $curPoint->x + $cmd[1];
                    $tP->y = $curPoint->y + $cmd[2];
                    list ($x, $y) = $this->applyTransformToPoint($txf, $tP, $args);
                    $cmd[1] = $x;
                    $cmd[2] = $y;

                    $tP->x = $curPoint->x + $cmd[3];
                    $tP->y = $curPoint->y + $cmd[4];
                    list ($x, $y) = $this->applyTransformToPoint($txf, $tP, $args);
                    $cmd[3] = $x;
                    $cmd[4] = $y;

                    $curPoint->x += $cmd[5];
                    $curPoint->y += $cmd[6];
                    list ($x, $y) = $this->applyTransformToPoint($txf, $curPoint, $args);

                    $curPoint->x = $x;
                    $curPoint->y = $y;
                    $cmd[5] = $x;
                    $cmd[6] = $y;

                    break;
                case 'C':
                    $curPoint->x = $cmd[1];
                    $curPoint->y = $cmd[2];
                    list ($x, $y) = $this->applyTransformToPoint($txf, $curPoint, $args);
                    $cmd[1] = $x;
                    $cmd[2] = $y;

                    $curPoint->x = $cmd[3];
                    $curPoint->y = $cmd[4];
                    list ($x, $y) = $this->applyTransformToPoint($txf, $curPoint, $args);
                    $cmd[3] = $x;
                    $cmd[4] = $y;

                    $curPoint->x = $cmd[5];
                    $curPoint->y = $cmd[6];
                    list ($x, $y) = $this->applyTransformToPoint($txf, $curPoint, $args);

                    $curPoint->x = $x;
                    $curPoint->y = $y;
                    $cmd[5] = $x;
                    $cmd[6] = $y;

                    break;

                case 's':
                case 'S':

                case 'q':
                case 'Q':

                case 't':
                case 'T':

                case 'a':
                    break;

                case 'A':
                    /*
         * This radius is relative to the start and end points, so it makes
         * sense to scale, rotate, or skew it, but not translate it.
         */
                    if ($txf != 'translate') {
                        $curPoint->x = $cmd[1];
                        $curPoint->y = $cmd[2];
                        list ($x, $y) = $this->applyTransformToPoint($txf, $curPoint, $args);
                        $cmd[1] = $x;
                        $cmd[2] = $y;
                    }

                    $curPoint->x = $cmd[6];
                    $curPoint->y = $cmd[7];
                    list ($x, $y) = $this->applyTransformToPoint($txf, $curPoint, $args);
                    $curPoint->x = $x;
                    $curPoint->y = $y;
                    $cmd[6] = $x;
                    $cmd[7] = $y;

                    break;
            }
        }
    }

    public function render(): string
    {
        $startPoint = array_shift($this->points);
        $endPoint = $this->points[count($this->points) - 1];

        $out = "<g id=\"group{$this->name}\">\n";

        /*
     * If someone has specified one of our special object types, we are going
     * to want to completely override any of the pathing that we would have
     * done otherwise, but we defer until here to do anything about it because
     * we need information about the object we're replacing.
     */
        if (isset($this->options['a2s:type']) &&
            isset(CustomObjects::$objects[$this->options['a2s:type']])) {
            $object = CustomObjects::$objects[$this->options['a2s:type']];

            /* Again, if no fill was specified, specify one. */
            if (!isset($this->options['fill'])) {
                $this->options['fill'] = '#fff';
            }

            /*
       * We don't care so much about the area, but we do care about the width
       * and height of the object. All of our "custom" objects are implemented
       * in 100x100 space, which makes the transformation marginally easier.
       */
            $minX = $startPoint->x;
            $maxX = $minX;
            $minY = $startPoint->y;
            $maxY = $minY;
            foreach ($this->points as $p) {
                if ($p->x < $minX) {
                    $minX = $p->x;
                } elseif ($p->x > $maxX) {
                    $maxX = $p->x;
                }
                if ($p->y < $minY) {
                    $minY = $p->y;
                } elseif ($p->y > $maxY) {
                    $maxY = $p->y;
                }
            }

            $objW = $maxX - $minX;
            $objH = $maxY - $minY;

            if (isset($this->options['a2s:link'])) {
                $out .= "<a xlink:href=\"" . $this->options['a2s:link'] . "\">";
            }

            $i = 0;
            foreach ($object as $o) {
                $id = self::$id++;
                $out .= "\t<path id=\"path{$this->name}\" d=\"";

                $oW = $o['width'];
                $oH = $o['height'];

                $this->applyTransformToPath('scale', $o, array($objW / $oW, $objH / $oH));
                $this->applyTransformToPath('translate', $o, array($minX, $minY));

                foreach ($o['path'] as $cmd) {
                    $out .= join(' ', $cmd) . ' ';
                }
                $out .= '" ';

                /* Don't add options to sub-paths */
                if ($i++ < 1) {
                    foreach ($this->options as $opt => $val) {
                        if (strpos($opt, 'a2s:', 0) === 0) {
                            continue;
                        }
                        $out .= "$opt=\"$val\" ";
                    }
                }

                $out .= " />\n";
            }

            if (count($this->text) > 0) {
                foreach ($this->text as $text) {
                    $out .= "\t" . $text->render() . "\n";
                }
            }

            if (isset($this->options['a2s:link'])) {
                $out .= "</a>";
            }

            $out .= "</g>\n";

            /* Bazinga. */
            return $out;
        }

        /*
     * Nothing fancy here -- this is just rendering for our standard
     * polygons.
     *
     * Our start point is represented by a single moveto command (unless the
     * start point is curved) as the shape will be closed with the Z command
     * automatically if it is a closed shape. If we have a control point, we
     * have to go ahead and draw the curve.
     */
        if (($startPoint->flags & Point::CONTROL)) {
            $cX = $startPoint->x;
            $cY = $startPoint->y;
            $sX = $startPoint->x;
            $sY = $startPoint->y + 10;
            $eX = $startPoint->x + 10;
            $eY = $startPoint->y;

            $path = "M {$sX} {$sY} Q {$cX} {$cY} {$eX} {$eY} ";
        } else {
            $path = "M {$startPoint->x} {$startPoint->y} ";
        }

        $prevP = $startPoint;
        $bound = count($this->points);
        for ($i = 0; $i < $bound; $i++) {
            $p = $this->points[$i];

            /*
       * Handle quadratic Bezier curves. NOTE: This algorithm for drawing
       * the curves only works if the shapes are drawn in a clockwise
       * manner.
       */
            if (($p->flags & Point::CONTROL)) {
                /* Our control point is always the original corner */
                $cX = $p->x;
                $cY = $p->y;

                $sX = 0;
                $sY = 0;
                $eX = 0;
                $eY = 0;

                /* Need next point to determine which way to turn */
                if ($i === count($this->points) - 1) {
                    $nP = $startPoint;
                } else {
                    $nP = $this->points[$i + 1];
                }

                if ($prevP->x === $p->x) {
                    /**
                     * If we are on the same vertical axis, our starting X coordinate
                     * is the same as the control point coordinate.
                     */
                    $sX = $p->x;

                    /* Offset start point from control point in the proper direction */
                    if ($prevP->y < $p->y) {
                        $sY = $p->y - 10;
                    } else {
                        $sY = $p->y + 10;
                    }

                    $eY = $p->y;
                    /* Offset end point from control point in the proper direction */
                    if ($nP->x < $p->x) {
                        $eX = $p->x - 10;
                    } else {
                        $eX = $p->x + 10;
                    }
                } elseif ($prevP->y == $p->y) {
                    /* Horizontal decisions mirror vertical's above */
                    $sY = $p->y;
                    if ($prevP->x < $p->x) {
                        $sX = $p->x - 10;
                    } else {
                        $sX = $p->x + 10;
                    }

                    $eX = $p->x;
                    if ($nP->y <= $p->y) {
                        $eY = $p->y - 10;
                    } else {
                        $eY = $p->y + 10;
                    }
                }

                $path .= "L {$sX} {$sY} Q {$cX} {$cY} {$eX} {$eY} ";
            } else {
                /* The excruciating difficulty of drawing a straight line */
                $path .= "L {$p->x} {$p->y} ";
            }

            $prevP = $p;
        }

        if ($this->isClosed()) {
            $path .= 'Z';
        }

        $id = self::$id++;

        /* Add markers if necessary. */
        if ($startPoint->flags & Point::SMARKER) {
            $this->options["marker-start"] = "url(#Pointer)";
        } elseif ($startPoint->flags & Point::IMARKER) {
            $this->options["marker-start"] = "url(#iPointer)";
        }

        if ($endPoint->flags & Point::SMARKER) {
            $this->options["marker-end"] = "url(#Pointer)";
        } elseif ($endPoint->flags & Point::IMARKER) {
            $this->options["marker-end"] = "url(#iPointer)";
        }

        /**
         * SVG objects without a fill will be transparent, and this looks so
         * terrible with the drop-shadow effect. Any objects that aren't filled
         * automatically get a white fill.
         */
        if ($this->isClosed() && !isset($this->options['fill'])) {
            $this->options['fill'] = '#fff';
        }

        $out_p = "\t<path id=\"path{$this->name}\" ";
        foreach ($this->options as $opt => $val) {
            if (strpos($opt, 'a2s:', 0) === 0) {
                if ($opt === 'a2s:link') {
                    $alnk = $val;
                }
                continue;
            }
            $out_p .= "$opt=\"$val\" ";
        }
        if (isset($alnk)) {
            $out_p = "\t<a xlink:href=\"" . $alnk . "\">" . $out_p;
        }
        $out_p .= "d=\"{$path}\" />\n";

        if (count($this->text) > 0) {
            foreach ($this->text as $text) {
                $out_p .= "\t" . $text->render() . "\n";
            }
        }

        if (isset($alnk)) {
            $out_p .= '</a>';
        }
        $out .= $out_p;

        $bound = count($this->ticks);
        for ($i = 0; $i < $bound; $i++) {
            $t = $this->ticks[$i];
            if ($t->flags & Point::DOT) {
                $out .= "<circle cx=\"{$t->x}\" cy=\"{$t->y}\" r=\"3\" fill=\"black\" />";
            } elseif ($t->flags & Point::TICK) {
                $x1 = $t->x - 4;
                $y1 = $t->y - 4;
                $x2 = $t->x + 4;
                $y2 = $t->y + 4;
                $out .= "<line x1=\"$x1\" y1=\"$y1\" x2=\"$x2\" y2=\"$y2\" stroke-width=\"1\" />";

                $x1 = $t->x + 4;
                $y1 = $t->y - 4;
                $x2 = $t->x - 4;
                $y2 = $t->y + 4;
                $out .= "<line x1=\"$x1\" y1=\"$y1\" x2=\"$x2\" y2=\"$y2\" stroke-width=\"1\" />";
            }
        }

        $out .= "</g>\n";
        return $out;
    }
}
