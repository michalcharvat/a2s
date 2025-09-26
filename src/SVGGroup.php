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
 * Groups objects together and sets common properties for the objects in the
 * group.
 */

class SVGGroup
{
    /**
     * @var array<string, array<SVGPath|SVGText>>
     */
    private array $groups;
    private ?string $curGroup;

    /**
     * @var array<string>
     */
    private array $groupStack;

    /**
     * @var array<string, array<string, string>>
     */
    private array $options;

    public function __construct()
    {
        $this->groups = [];
        $this->groupStack = [];
        $this->options = [];
    }

    /**
     * @param string $groupName
     * @return array<SVGPath|SVGText>
     */
    public function getGroup(string $groupName): array
    {
        return $this->groups[$groupName];
    }

    public function pushGroup(string $groupName): void
    {
        if (!isset($this->groups[$groupName])) {
            $this->groups[$groupName] = [];
            $this->options[$groupName] = [];
        }

        $this->groupStack[] = $groupName;
        $this->curGroup = $groupName;
    }

    public function popGroup(): void
    {
        /**
         * Remove the last group and fetch the current one. array_pop will return
         * NULL for an empty array, so this is safe to do when only one element
         * is left.
         */
        array_pop($this->groupStack);
        $this->curGroup = array_pop($this->groupStack);
    }

    public function addObject(SVGPath|SVGText $o): void
    {
        $this->groups[$this->curGroup][] = $o;
    }

    public function setOption(string $opt, string $val): void
    {
        $this->options[$this->curGroup][$opt] = $val;
    }

    public function render(): string
    {
        $out = '';

        foreach ($this->groups as $groupName => $objects) {
            $out .= "<g id=\"{$groupName}\" ";
            foreach ($this->options[$groupName] as $opt => $val) {
                if (strpos($opt, 'a2s:', 0) === 0) {
                    continue;
                }
                $out .= "$opt=\"$val\" ";
            }
            $out .= ">\n";

            foreach ($objects as $obj) {
                $out .= $obj->render();
            }

            $out .= "</g>\n";
        }

        return $out;
    }
}
