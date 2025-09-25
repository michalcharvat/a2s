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


/*
 * CustomObjects allows users to create their own custom SVG paths and use
 * them as box types with a2s:type references.
 *
 * Paths must have width and height set, and must not span multiple lines.
 * Multiple paths can be specified, one path per line. All objects must
 * reside in the same directory.
 *
 * File operations are horribly slow, so we make a best effort to avoid
 * as many as possible:
 *
 *  * If the directory mtime hasn't changed, we attempt to load our
 *    objects from a cache file.
 *
 *  * If this file doesn't exist, can't be read, or the mtime has
 *    changed, we scan the directory and update files that have changed
 *    based on their mtime.
 *
 *  * We attempt to save our cache in a temporary directory. It's volatile
 *    but also requires no configuration.
 *
 * We could do a bit better by utilizing APC's shared memory storage, which
 * would help greatly when running on a server.
 *
 * Note that the path parser isn't foolproof, mostly because PHP isn't the
 * greatest language ever for implementing a parser.
 */

class CustomObjects
{
    public static $objects = array();

    /**
     * Closures / callable function names / whatever for integrating non-default
     * loading and storage functionality.
     */

    /**
     * @var ?callable
     */
    public static $loadCacheFn = null;

    /**
     * @var ?callable
     */
    public static $storCacheFn = null;

    /**
     * @var ?callable
     */
    public static $loadObjsFn = null;

    protected static $cacheTime = null;


    public static function loadObjects()
    {
        global $conf;
        //$cacheFile = $conf['cachedir'] . '/plugin.asciitosvg.objcache';
        $cacheFile = tempnam(sys_get_temp_dir(), 'plugin.asciitosvg.objcache');
        $dir = dirname(__DIR__) . '/objects';
        if (is_callable(self::$loadCacheFn)) {
            /**
             * Should return exactly what was given to the $storCacheFn when it was
             * last called, or null if nothing can be loaded.
             */
            $fn = self::$loadCacheFn;
            self::$objects = $fn();
            return;
        }
        if (is_readable($cacheFile) && is_readable($dir)) {
            static::$cacheTime = filemtime($cacheFile);

            if (filemtime($dir) <= filemtime($cacheFile)) {
                self::$objects = unserialize(file_get_contents($cacheFile));
                return;
            }
        } else if (file_exists($cacheFile)) {
            return;
        }

        if (is_callable(self::$loadObjsFn)) {
            /**
             * Returns an array of arrays of path information. The innermost arrays
             * (containing the path information) contain the path name, the width of
             * the bounding box, the height of the bounding box, and the path
             * command. This interface does *not* want the path's XML tag. An array
             * returned from here containing two objects that each have 1 line would
             * look like:
             *
             * array (
             *   array (
             *     name => 'pathA',
             *     paths => array (
             *       array ('width' => 10, 'height' => 10, 'path' => 'M 0 0 L 10 10'),
             *       array ('width' => 10, 'height' => 10, 'path' => 'M 0 10 L 10 0'),
             *     ),
             *   ),
             *   array (
             *     name => 'pathB',
             *     paths => array (
             *       array ('width' => 10, 'height' => 10, 'path' => 'M 0 5 L 5 10'),
             *       array ('width' => 10, 'height' => 10, 'path' => 'M 5 10 L 10 5'),
             *     ),
             *   ),
             * );
             */
            $fn = self::$loadObjsFn;
            $objs = $fn();

            $i = 0;
            foreach ($objs as $obj) {
                foreach ($obj['paths'] as $path) {
                    self::$objects[$obj['name']][$i]['width'] = $path['width'];
                    self::$objects[$obj['name']][$i]['height'] = $path['height'];
                    self::$objects[$obj['name']][$i++]['path'] =
                        self::parsePath($path['path']);
                }
            }
        } else {
            $ents = scandir($dir);
            foreach ($ents as $ent) {
                $file = "{$dir}/{$ent}";
                $base = substr($ent, 0, -5);
                if (substr($ent, -5) === '.path' && is_readable($file)) {
                    if (isset(self::$objects[$base]) &&
                        filemtime($file) <= self::$cacheTime) {
                        continue;
                    }

                    $lines = file($file);

                    $i = 0;
                    foreach ($lines as $line) {
                        preg_match('/width="(\d+)/', $line, $m);
                        $width = $m[1];
                        preg_match('/height="(\d+)/', $line, $m);
                        $height = $m[1];
                        preg_match('/d="([^"]+)"/', $line, $m);
                        $path = $m[1];

                        self::$objects[$base][$i]['width'] = $width;
                        self::$objects[$base][$i]['height'] = $height;
                        self::$objects[$base][$i++]['path'] = self::parsePath($path);
                    }
                }
            }
        }

        if (is_callable(self::$storCacheFn)) {
            $fn = self::$storCacheFn;
            $fn(self::$objects);
        } else {
            file_put_contents($cacheFile, serialize(self::$objects));
        }
    }

    private static function parsePath(string $path): array
    {
        $stream = fopen("data://text/plain,{$path}", 'rb');

        $P = new SVGPathParser();
        $S = new Yylex($stream);

        while ($t = $S->nextToken()) {
            $P->SVGPath($t->type, $t);
        }
        /* Force shift/reduce of last token. */
        $P->SVGPath(0);

        fclose($stream);

        $cmdArr = array();
        $i = 0;
        foreach ($P->commands as $cmd) {
            foreach ($cmd as $arg) {
                $arg = (array)$arg;
                $cmdArr[$i][] = $arg['value'];
            }
            $i++;
        }

        return $cmdArr;
    }
}
