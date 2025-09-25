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

class Yylex extends JLexBase
{
    const YY_BUFFER_SIZE = 512;
    const YY_F = -1;
    const YY_NO_STATE = -1;
    const YY_NOT_ACCEPT = 0;
    const YY_START = 1;
    const YY_END = 2;
    const YY_NO_ANCHOR = 4;
    const YY_BOL = 128;
    var $YY_EOF = 129;

    /* w/e */

    function __construct($stream)
    {
        parent::__construct($stream);
        $this->yy_lexical_state = self::YYINITIAL;
    }

    const YYINITIAL = 0;
    protected static array $yy_state_dtrans = [
        0
    ];
    protected static array $yy_acpt = [
        /* 0 */
        self::YY_NOT_ACCEPT,
        /* 1 */
        self::YY_NO_ANCHOR,
        /* 2 */
        self::YY_NO_ANCHOR,
        /* 3 */
        self::YY_NO_ANCHOR,
        /* 4 */
        self::YY_NO_ANCHOR,
        /* 5 */
        self::YY_NO_ANCHOR,
        /* 6 */
        self::YY_NO_ANCHOR,
        /* 7 */
        self::YY_NO_ANCHOR,
        /* 8 */
        self::YY_NO_ANCHOR,
        /* 9 */
        self::YY_NO_ANCHOR,
        /* 10 */
        self::YY_NO_ANCHOR,
        /* 11 */
        self::YY_NO_ANCHOR,
        /* 12 */
        self::YY_NO_ANCHOR,
        /* 13 */
        self::YY_NO_ANCHOR,
        /* 14 */
        self::YY_NO_ANCHOR,
        /* 15 */
        self::YY_NO_ANCHOR,
        /* 16 */
        self::YY_NO_ANCHOR,
        /* 17 */
        self::YY_NO_ANCHOR,
        /* 18 */
        self::YY_NO_ANCHOR,
        /* 19 */
        self::YY_NO_ANCHOR,
        /* 20 */
        self::YY_NO_ANCHOR,
        /* 21 */
        self::YY_NOT_ACCEPT,
        /* 22 */
        self::YY_NO_ANCHOR,
        /* 23 */
        self::YY_NO_ANCHOR,
        /* 24 */
        self::YY_NO_ANCHOR,
        /* 25 */
        self::YY_NO_ANCHOR,
        /* 26 */
        self::YY_NO_ANCHOR,
        /* 27 */
        self::YY_NO_ANCHOR,
        /* 28 */
        self::YY_NOT_ACCEPT,
        /* 29 */
        self::YY_NO_ANCHOR,
        /* 30 */
        self::YY_NOT_ACCEPT,
        /* 31 */
        self::YY_NO_ANCHOR,
        /* 32 */
        self::YY_NOT_ACCEPT,
        /* 33 */
        self::YY_NOT_ACCEPT,
        /* 34 */
        self::YY_NOT_ACCEPT,
        /* 35 */
        self::YY_NOT_ACCEPT,
        /* 36 */
        self::YY_NOT_ACCEPT,
        /* 37 */
        self::YY_NOT_ACCEPT,
        /* 38 */
        self::YY_NOT_ACCEPT
    ];
    protected static array $yy_cmap = [
        19, 19, 19, 19, 19, 19, 19, 19, 19, 18, 18, 19, 18, 18, 19, 19, 19, 19, 19, 19,
        19, 19, 19, 19, 19, 19, 19, 19, 19, 19, 19, 19, 18, 19, 19, 19, 19, 19, 19, 19,
        19, 19, 19, 2, 18, 5, 6, 19, 3, 3, 4, 4, 4, 4, 4, 4, 4, 4, 19, 19,
        19, 19, 19, 19, 19, 17, 19, 14, 19, 7, 19, 19, 11, 19, 19, 19, 10, 8, 19, 19,
        19, 13, 19, 15, 16, 19, 12, 19, 19, 19, 9, 19, 19, 19, 19, 19, 19, 17, 19, 14,
        19, 7, 19, 19, 11, 19, 19, 19, 10, 8, 19, 19, 19, 13, 19, 15, 16, 19, 12, 19,
        19, 19, 9, 19, 1, 19, 19, 19, 0, 0,
    ];

    protected static array $yy_rmap = [
        0, 1, 1, 2, 3, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 4, 5, 6, 7,
        8, 9, 3, 10, 11, 12, 13, 14, 15, 9, 16, 1, 17, 11, 18, 19, 12, 13, 14,
    ];

    protected static array $yy_nxt = [
        [
            1, 2, 3, 22, 4, 23, 29, 31, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 31,
        ],
        [
            -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1,
        ],
        [
            -1, -1, -1, 4, 4, -1, 21, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1,
        ],
        [
            -1, -1, -1, 4, 4, -1, 16, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1,
        ],
        [
            -1, -1, -1, 18, 18, -1, -1, 30, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1,
        ],
        [
            -1, -1, -1, 17, 17, -1, 19, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1,
        ],
        [
            -1, -1, -1, 18, 18, -1, -1, 32, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1,
        ],
        [
            -1, -1, -1, 20, 20, -1, -1, 34, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1,
        ],
        [
            -1, -1, -1, 20, 20, -1, -1, 35, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1,
        ],
        [
            -1, -1, -1, 18, 18, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1,
        ],
        [
            -1, -1, -1, 17, 17, -1, 28, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1,
        ],
        [
            -1, -1, -1, 24, 24, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1,
        ],
        [
            -1, -1, -1, 25, 25, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1,
        ],
        [
            -1, -1, -1, 26, 26, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1,
        ],
        [
            -1, -1, -1, 27, 27, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1,
        ],
        [
            -1, -1, -1, 20, 20, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1,
        ],
        [
            -1, -1, 33, 24, 24, 33, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1,
        ],
        [
            -1, -1, 36, 25, 25, 36, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1,
        ],
        [
            -1, -1, 37, 26, 26, 37, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1,
        ],
        [
            -1, -1, 38, 27, 27, 38, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1,
        ],
    ];

    /**
     * @throws \Exception
     */
    public function nextToken(): ?JLexToken
    {
        $yy_anchor = self::YY_NO_ANCHOR;
        $yy_state = self::$yy_state_dtrans[$this->yy_lexical_state];
        $yy_next_state = self::YY_NO_STATE;
        $yy_last_accept_state = self::YY_NO_STATE;
        $yy_initial = true;

        $this->yy_mark_start();
        $yy_this_accept = self::$yy_acpt[$yy_state];
        if (self::YY_NOT_ACCEPT != $yy_this_accept) {
            $yy_last_accept_state = $yy_state;
            $this->yy_mark_end();
        }
        while (true) {
            if ($yy_initial && $this->yy_at_bol) $yy_lookahead = self::YY_BOL;
            else $yy_lookahead = $this->yy_advance();
            $yy_next_state = self::$yy_nxt[self::$yy_rmap[$yy_state]][self::$yy_cmap[$yy_lookahead]];
            if ($this->YY_EOF == $yy_lookahead && true == $yy_initial) {
                return null;
            }
            if (self::YY_F != $yy_next_state) {
                $yy_state = $yy_next_state;
                $yy_initial = false;
                $yy_this_accept = self::$yy_acpt[$yy_state];
                if (self::YY_NOT_ACCEPT != $yy_this_accept) {
                    $yy_last_accept_state = $yy_state;
                    $this->yy_mark_end();
                }
            } else {
                if (self::YY_NO_STATE == $yy_last_accept_state) {
                    throw new \Exception("Lexical Error: Unmatched Input.");
                } else {
                    $yy_anchor = self::$yy_acpt[$yy_last_accept_state];
                    if (0 != (self::YY_END & $yy_anchor)) {
                        $this->yy_move_end();
                    }
                    $this->yy_to_mark();
                    switch ($yy_last_accept_state) {
                        case 1:
                        case -2:
                            break;
                        case 2:
                            return $this->createToken(SVGPathParser::TK_FLAG);
                        case -3:
                            break;
                        case 3:
                        case -4:
                            break;
                        case 4:
                            return $this->createToken(SVGPathParser::TK_POSNUM);
                        case -5:
                            break;
                        case 5:
                            return $this->createToken(SVGPathParser::TK_MCMD);
                        case -6:
                            break;
                        case 6:
                            return $this->createToken(SVGPathParser::TK_ZCMD);
                        case -7:
                            break;
                        case 7:
                            return $this->createToken(SVGPathParser::TK_LCMD);
                        case -8:
                            break;
                        case 8:
                            return $this->createToken(SVGPathParser::TK_HCMD);
                        case -9:
                            break;
                        case 9:
                            return $this->createToken(SVGPathParser::TK_VCMD);
                        case -10:
                            break;
                        case 10:
                            return $this->createToken(SVGPathParser::TK_QCMD);
                        case -11:
                            break;
                        case 11:
                            return $this->createToken(SVGPathParser::TK_CCMD);
                        case -12:
                            break;
                        case 12:
                            return $this->createToken(SVGPathParser::TK_SCMD);
                        case -13:
                            break;
                        case 13:
                            return $this->createToken(SVGPathParser::TK_TCMD);
                        case -14:
                            break;
                        case 14:
                            return $this->createToken(SVGPathParser::TK_ACMD);
                        case -15:
                            break;
                        case 15:
                        case -16:
                            break;
                        case 16:
                            return $this->createToken(SVGPathParser::TK_POSNUM);
                        case -17:
                            break;
                        case 17:
                            return $this->createToken(SVGPathParser::TK_POSNUM);
                        case -18:
                            break;
                        case 18:
                            return $this->createToken(SVGPathParser::TK_POSNUM);
                        case -19:
                            break;
                        case 19:
                            return $this->createToken(SVGPathParser::TK_POSNUM);
                        case -20:
                            break;
                        case 20:
                            return $this->createToken(SVGPathParser::TK_POSNUM);
                        case -21:
                            break;
                        case 22:
                            return $this->createToken(SVGPathParser::TK_FLAG);
                        case -22:
                            break;
                        case 23:
                        case -23:
                            break;
                        case 24:
                            return $this->createToken(SVGPathParser::TK_POSNUM);
                        case -24:
                            break;
                        case 25:
                            return $this->createToken(SVGPathParser::TK_POSNUM);
                        case -25:
                            break;
                        case 26:
                            return $this->createToken(SVGPathParser::TK_POSNUM);
                        case -26:
                            break;
                        case 27:
                            return $this->createToken(SVGPathParser::TK_POSNUM);
                        case -27:
                            break;
                        case 29:
                        case -28:
                            break;
                        case 31:
                        case -29:
                            break;
                        default:
                            $this->yy_error('INTERNAL', false);
                        case -1:
                    }
                    $yy_initial = true;
                    $yy_state = self::$yy_state_dtrans[$this->yy_lexical_state];
                    $yy_next_state = self::YY_NO_STATE;
                    $yy_last_accept_state = self::YY_NO_STATE;
                    $this->yy_mark_start();
                    $yy_this_accept = self::$yy_acpt[$yy_state];
                    if (self::YY_NOT_ACCEPT != $yy_this_accept) {
                        $yy_last_accept_state = $yy_state;
                        $this->yy_mark_end();
                    }
                }
            }
        }
    }
}
