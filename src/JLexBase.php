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

class JLexBase
{
    const YY_F = -1;
    const YY_NO_STATE = -1;
    const YY_NOT_ACCEPT = 0;
    const YY_START = 1;
    const YY_END = 2;
    const YY_NO_ANCHOR = 4;
    protected int $YY_EOF;

    /**
     * @var resource
     */
    protected $yy_reader;
    protected string $yy_buffer;
    protected int $yy_buffer_read;
    protected int $yy_buffer_index;
    protected int $yy_buffer_start;
    protected int $yy_buffer_end;
    protected int $yychar = 0;
    protected int $yycol = 0;
    protected int $yyline = 0;
    protected bool $yy_at_bol;
    protected int $yy_lexical_state;
    protected bool $yy_last_was_cr = false;
    protected bool $yy_count_lines = false;
    protected bool $yy_count_chars = false;
    protected ?string $yyfilename = null;

    /**
     * @param resource $stream
     */
    public function __construct($stream)
    {
        $this->yy_reader = $stream;
        $meta = stream_get_meta_data($stream);
        if (!isset($meta['uri'])) {
            $this->yyfilename = '<<input>>';
        } else {
            $this->yyfilename = $meta['uri'];
        }

        $this->yy_buffer = "";
        $this->yy_buffer_read = 0;
        $this->yy_buffer_index = 0;
        $this->yy_buffer_start = 0;
        $this->yy_buffer_end = 0;
        $this->yychar = 0;
        $this->yyline = 1;
        $this->yy_at_bol = true;
    }

    protected function yybegin(int $state): void
    {
        $this->yy_lexical_state = $state;
    }

    protected function yy_advance(): int
    {
        if ($this->yy_buffer_index < $this->yy_buffer_read) {
            if (!isset($this->yy_buffer[$this->yy_buffer_index])) {
                return $this->YY_EOF;
            }
            return ord($this->yy_buffer[$this->yy_buffer_index++]);
        }
        if ($this->yy_buffer_start !== 0) {
            /* shunt */
            $j = $this->yy_buffer_read - $this->yy_buffer_start;
            $this->yy_buffer = substr($this->yy_buffer, $this->yy_buffer_start, $j);
            $this->yy_buffer_end -= $this->yy_buffer_start;
            $this->yy_buffer_start = 0;
            $this->yy_buffer_read = $j;
            $this->yy_buffer_index = $j;

            $data = fread($this->yy_reader, 8192);
            if ($data === false || !strlen($data)) {
                return $this->YY_EOF;
            }
            $this->yy_buffer .= $data;
            $this->yy_buffer_read += strlen($data);
        }

        while ($this->yy_buffer_index >= $this->yy_buffer_read) {
            $data = fread($this->yy_reader, 8192);
            if ($data === false || !strlen($data)) {
                return $this->YY_EOF;
            }
            $this->yy_buffer .= $data;
            $this->yy_buffer_read += strlen($data);
        }
        return ord($this->yy_buffer[$this->yy_buffer_index++]);
    }

    protected function yy_move_end(): void
    {
        if ($this->yy_buffer_end > $this->yy_buffer_start && $this->yy_buffer[$this->yy_buffer_end - 1] === "\n") {
            $this->yy_buffer_end--;
        }
        if ($this->yy_buffer_end > $this->yy_buffer_start && $this->yy_buffer[$this->yy_buffer_end - 1] === "\r") {
            $this->yy_buffer_end--;
        }
    }

    protected function yy_mark_start(): void
    {
        if ($this->yy_count_lines || $this->yy_count_chars) {
            if ($this->yy_count_lines) {
                for ($i = $this->yy_buffer_start; $i < $this->yy_buffer_index; ++$i) {
                    if ("\n" === $this->yy_buffer[$i] && !$this->yy_last_was_cr) {
                        ++$this->yyline;
                        $this->yycol = 0;
                    }
                    if ("\r" === $this->yy_buffer[$i]) {
                        ++$this->yyline;
                        $this->yycol = 0;
                        $this->yy_last_was_cr = true;
                    } else {
                        $this->yy_last_was_cr = false;
                    }
                }
            }
            if ($this->yy_count_chars) {
                $this->yychar += $this->yy_buffer_index - $this->yy_buffer_start;
                $this->yycol += $this->yy_buffer_index - $this->yy_buffer_start;
            }
        }
        $this->yy_buffer_start = $this->yy_buffer_index;
    }

    protected function yy_mark_end(): void
    {
        $this->yy_buffer_end = $this->yy_buffer_index;
    }

    protected function yy_to_mark(): void
    {
        #echo "yy_to_mark: setting buffer index to ", $this->yy_buffer_end, "\n";
        $this->yy_buffer_index = $this->yy_buffer_end;
        $this->yy_at_bol = ($this->yy_buffer_end > $this->yy_buffer_start) &&
            ("\r" === $this->yy_buffer[$this->yy_buffer_end - 1] ||
                "\n" === $this->yy_buffer[$this->yy_buffer_end - 1] ||
                2028 /* unicode LS */ == $this->yy_buffer[$this->yy_buffer_end - 1] ||
                2029 /* unicode PS */ == $this->yy_buffer[$this->yy_buffer_end - 1]);
    }

    protected function yytext(): string
    {
        return substr($this->yy_buffer, $this->yy_buffer_start,
            $this->yy_buffer_end - $this->yy_buffer_start);
    }

    protected function yylength(): int
    {
        return $this->yy_buffer_end - $this->yy_buffer_start;
    }

    /**
     * @var array<string>
     */
    static array $yy_error_string = [
        'INTERNAL' => "Error: internal error.\n",
        'MATCH' => "Error: Unmatched input.\n"
    ];

    protected function yy_error(string $code, bool $fatal = false): void
    {
        print self::$yy_error_string[$code];
        flush();
        if ($fatal) {
            throw new \Exception("JLex fatal error " . self::$yy_error_string[$code]);
        }
    }

    /* creates an annotated token */
    public function createToken(?int $type = null): JLexToken
    {
        if ($type === null) {
            $type = $this->yytext();
        }
        $tok = new JLexToken($type);
        $this->annotateToken($tok);
        return $tok;
    }

    /* annotates a token with a value and source positioning */
    public function annotateToken(JLexToken $tok): void
    {
        $tok->value = $this->yytext();
        $tok->col = $this->yycol;
        $tok->line = $this->yyline;
        $tok->filename = $this->yyfilename;
    }
}
