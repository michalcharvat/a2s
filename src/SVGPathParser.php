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

class SVGPathParser
{
    /**
     * @var array<string, array>
     */
    public array $commands = [];

    /**
     * @var int $yyidx
     */
    public $yyidx = -1;                    /* Index of top element in stack */
    public int $yyerrcnt;                 /* Shifts left before out of the error */
    // SVGPathARG_SDECL                /* A place to hold %extra_argument */
    /** @var array<SVGPathyyStackEntry> */
    public array /* yyStackEntry */
        $yystack = [/* of YYSTACKDEPTH elements */
    ];  /* The parser's stack */

    /**
     * @var ?resource
     */
    public $yyTraceFILE = null;
    public ?string $yyTracePrompt = null;


    /* Next is all token values, in a form suitable for use by makeheaders.
** This section will be null unless lemon is run with the -m switch.
*/
    /*
** These constants (all generated automatically by the parser generator)
** specify the various kinds of tokens (terminals) that the parser
** understands.
**
** Each symbol here is a terminal symbol in the grammar.
*/
    const TK_ANY = 1;
    const TK_MCMD = 2;
    const TK_ZCMD = 3;
    const TK_LCMD = 4;
    const TK_HCMD = 5;
    const TK_VCMD = 6;
    const TK_CCMD = 7;
    const TK_SCMD = 8;
    const TK_QCMD = 9;
    const TK_TCMD = 10;
    const TK_ACMD = 11;
    const TK_POSNUM = 12;
    const TK_FLAG = 13;
    const TK_NEGNUM = 14;
    /* The next thing included is series of defines which control
** various aspects of the generated parser.
**    YYCODETYPE         is the data type used for storing terminal
**                       and nonterminal numbers.  "unsigned char" is
**                       used if there are fewer than 250 terminals
**                       and nonterminals.  "int" is used otherwise.
**    YYNOCODE           is a number of type YYCODETYPE which corresponds
**                       to no legal terminal or nonterminal number.  This
**                       number is used to fill in empty slots of the hash
**                       table.
**    YYFALLBACK         If defined, this indicates that one or more tokens
**                       have fall-back values which should be used if the
**                       original value of the token will not parse.
**    YYACTIONTYPE       is the data type used for storing terminal
**                       and nonterminal numbers.  "unsigned char" is
**                       used if there are fewer than 250 rules and
**                       states combined.  "int" is used otherwise.
**    SVGPathTOKENTYPE     is the data type used for minor tokens given
**                       directly to the parser from the tokenizer.
**    YYMINORTYPE        is the data type used for all minor tokens.
**                       This is typically a union of many types, one of
**                       which is SVGPathTOKENTYPE.  The entry in the union
**                       for base tokens is called "yy0".
**    YYSTACKDEPTH       is the maximum depth of the parser's stack.
**    SVGPathARG_SDECL     A static variable declaration for the %extra_argument
**    SVGPathARG_PDECL     A parameter declaration for the %extra_argument
**    SVGPathARG_STORE     Code to store %extra_argument into yypParser
**    SVGPathARG_FETCH     Code to extract %extra_argument from yypParser
**    YYNSTATE           the combined number of states.
**    YYNRULE            the number of rules in the grammar
**    YYERRORSYMBOL      is the code number of the error symbol.  If not
**                       defined, then do no error processing.
*/
    const YYNOCODE = 48;
    const YYWILDCARD = 1;
#define SVGPathTOKENTYPE void*
    const YYSTACKDEPTH = 100;
    const YYNSTATE = 74;
    const YYNRULE = 52;
    const YYERRORSYMBOL = 15;

    /* since we cant use expressions to initialize these as class
   * constants, we do so during parser init. */
    public int $YY_NO_ACTION;
    public int $YY_ACCEPT_ACTION;
    public int $YY_ERROR_ACTION;

    /* Next are that tables used to determine what action to take based on the
** current state and lookahead token.  These tables are used to implement
** functions that take a state number and lookahead value and return an
** action integer.
**
** Suppose the action integer is N.  Then the action is determined as
** follows
**
**   0 <= N < YYNSTATE                  Shift N.  That is, push the lookahead
**                                      token onto the stack and goto state N.
**
**   YYNSTATE <= N < YYNSTATE+YYNRULE   Reduce by rule N-YYNSTATE.
**
**   N == YYNSTATE+YYNRULE              A syntax error has occurred.
**
**   N == YYNSTATE+YYNRULE+1            The parser accepts its input.
**
**   N == YYNSTATE+YYNRULE+2            No such action.  Denotes unused
**                                      slots in the yy_action[] table.
**
** The action table is constructed as a single large table named yy_action[].
** Given state S and lookahead X, the action is computed as
**
**      yy_action[ yy_shift_ofst[S] + X ]
**
** If the index value yy_shift_ofst[S]+X is out of range or if the value
** yy_lookahead[yy_shift_ofst[S]+X] is not equal to X or if yy_shift_ofst[S]
** is equal to YY_SHIFT_USE_DFLT, it means that the action is not in the table
** and that yy_default[S] should be used instead.
**
** The formula above is for computing the action when the lookahead is
** a terminal symbol.  If the lookahead is a non-terminal (as occurs after
** a reduce action) then the yy_reduce_ofst[] array is used in place of
** the yy_shift_ofst[] array and YY_REDUCE_USE_DFLT is used in place of
** YY_SHIFT_USE_DFLT.
**
** The following are the tables generated in this section:
**
**  yy_action[]        A single table containing all actions.
**  yy_lookahead[]     A table containing the lookahead for each entry in
**                     yy_action.  Used to detect hash collisions.
**  yy_shift_ofst[]    For each state, the offset into yy_action for
**                     shifting terminals.
**  yy_reduce_ofst[]   For each state, the offset into yy_action for
**                     shifting non-terminals after a reduce.
**  yy_default[]       Default action for each state.
*/
    /**
     * @var array<int>
     */
    static array $yy_action = [
        /*     0 */
        2, 39, 44, 45, 46, 47, 48, 49, 50, 51,
        /*    10 */
        52, 43, 44, 45, 46, 47, 48, 49, 50, 51,
        /*    20 */
        52, 53, 7, 20, 16, 4, 5, 3, 9, 26,
        /*    30 */
        21, 17, 22, 22, 10, 67, 35, 12, 22, 8,
        /*    40 */
        73, 42, 1, 56, 56, 14, 18, 22, 34, 56,
        /*    50 */
        22, 11, 70, 40, 19, 30, 63, 22, 56, 15,
        /*    60 */
        60, 56, 22, 14, 21, 22, 22, 56, 56, 65,
        /*    70 */
        68, 36, 6, 56, 32, 101, 56, 56, 31, 127,
        /*    80 */
        25, 41, 1, 17, 27, 22, 57, 58, 59, 29,
        /*    90 */
        61, 22, 71, 24, 62, 13, 56, 22, 94, 94,
        /*   100 */
        94, 56, 56, 33, 37, 56, 22, 101, 56, 95,
        /*   110 */
        95, 95, 101, 66, 69, 22, 22, 56, 64, 23,
        /*   120 */
        54, 72, 22, 22, 55, 101, 56, 56, 101, 56,
        /*   130 */
        101, 101, 101, 56, 56, 56, 28, 38,
    ];

    /** @var array<int>  */
    static array $yy_lookahead = [
        /*     0 */
        20, 21, 22, 23, 24, 25, 26, 27, 28, 29,
        /*    10 */
        30, 21, 22, 23, 24, 25, 26, 27, 28, 29,
        /*    20 */
        30, 3, 4, 5, 6, 7, 8, 9, 10, 11,
        /*    30 */
        33, 33, 35, 35, 37, 38, 33, 13, 35, 41,
        /*    40 */
        42, 18, 19, 46, 46, 33, 43, 35, 33, 46,
        /*    50 */
        35, 39, 40, 31, 32, 33, 35, 35, 46, 32,
        /*    60 */
        33, 46, 35, 33, 33, 35, 35, 46, 46, 38,
        /*    70 */
        40, 45, 2, 46, 46, 47, 46, 46, 12, 16,
        /*    80 */
        17, 18, 19, 33, 12, 35, 12, 13, 14, 33,
        /*    90 */
        35, 35, 42, 34, 35, 33, 46, 35, 12, 13,
        /*   100 */
        14, 46, 46, 13, 33, 46, 35, 47, 46, 12,
        /*   110 */
        13, 14, 47, 33, 33, 35, 35, 46, 35, 36,
        /*   120 */
        33, 33, 35, 35, 35, 47, 46, 46, 47, 46,
        /*   130 */
        47, 47, 47, 46, 46, 46, 44, 45,
    ];
    const YY_SHIFT_USE_DFLT = -1;
    const YY_SHIFT_MAX = 33;

    /** @var array<int>  */
    static array $yy_shift_ofst = [
        /*     0 */
        70, 18, 18, 74, 74, 74, 74, 74, 74, 74,
        /*    10 */
        74, 74, 74, 74, 74, 74, 74, 74, 74, 74,
        /*    20 */
        74, 74, 74, 74, 74, 70, 66, 74, 66, 86,
        /*    30 */
        97, 72, 90, 24,
    ];
    const YY_REDUCE_USE_DFLT = -21;
    const YY_REDUCE_MAX = 28;

    /** @var array<int>  */
    static array $yy_reduce_ofst = [
        /*     0 */
        63, -20, -10, -2, -3, 12, 22, 27, 50, 3,
        /*    10 */
        31, 30, 71, 80, 81, 87, 83, 88, 15, 56,
        /*    20 */
        59, 62, 89, 21, 55, 23, 92, 28, 26,
    ];

    /** @var array<int>  */
    static array $yy_default = [
        /*     0 */
        126, 126, 77, 126, 126, 126, 126, 126, 110, 126,
        /*    10 */
        102, 106, 126, 126, 126, 93, 126, 126, 114, 126,
        /*    20 */
        126, 126, 126, 99, 96, 74, 126, 126, 117, 90,
        /*    30 */
        91, 126, 126, 126, 115, 116, 118, 120, 119, 79,
        /*    40 */
        89, 76, 75, 78, 80, 81, 82, 83, 84, 85,
        /*    50 */
        86, 87, 88, 92, 94, 121, 122, 123, 124, 125,
        /*    60 */
        95, 97, 98, 100, 101, 103, 105, 104, 107, 109,
        /*    70 */
        108, 111, 113, 112,
    ];

    /**
     * The next table maps tokens into fallback tokens.  If a construct
     ** like the following:
     **
     **      %fallback ID X Y Z.
     **
     ** appears in the grammer, then ID becomes a fallback token for X, Y,
     ** and Z.  Whenever one of the tokens X, Y, or Z is input to the parser
     ** but it does not parse, the type of the token is changed to ID and
     ** the parse is retried before an error is thrown.
     *
     * @var array<int>
     */
    static array $yyFallback = [];

    /**
     ** Turn parser tracing on by giving a stream to which to write the trace
     ** and a prompt to preface each trace message.  Tracing is turned off
     ** by making either argument NULL
     **
     ** Inputs:
     ** <ul>
     ** <li> A FILE* to which trace output should be written.
     **      If NULL, then tracing is turned off.
     ** <li> A prefix string written at the beginning of every
     **      line of trace output.  If NULL, then tracing is
     **      turned off.
     ** </ul>
     **
     ** Outputs:
     ** None.
     */
    /**
     * @param resource $TraceFILE
     * @param string|null $zTracePrompt
     * @return void
     */
    function SVGPathTrace(/* stream */ $TraceFILE, ?string $zTracePrompt): void
    {
        $this->yyTraceFILE = $TraceFILE;
        $this->yyTracePrompt = $zTracePrompt;
        if ($this->yyTraceFILE === null) {
            $this->yyTracePrompt = null;
        } else if ($this->yyTracePrompt === null) {
            $this->yyTraceFILE = null;
        }
    }

    /* For tracing shifts, the names of all terminals and nonterminals
** are required.  The following table supplies these names */
    /** @var array<string> */
    static array $yyTokenName = [
        '$', 'ANY', 'MCMD', 'ZCMD',
        'LCMD', 'HCMD', 'VCMD', 'CCMD',
        'SCMD', 'QCMD', 'TCMD', 'ACMD',
        'POSNUM', 'FLAG', 'NEGNUM', 'error',
        'svg_path', 'moveto_drawto_command_groups', 'moveto_drawto_command_group', 'moveto',
        'drawto_commands', 'drawto_command', 'closepath', 'lineto',
        'horizontal_lineto', 'vertical_lineto', 'curveto', 'smooth_curveto',
        'quadratic_bezier_curveto', 'smooth_quadratic_bezier_curveto', 'elliptical_arc', 'moveto_argument_sequence',
        'lineto_argument_sequence', 'coordinate_pair', 'horizontal_lineto_argument_sequence', 'coordinate',
        'vertical_lineto_argument_sequence', 'curveto_argument_sequence', 'curveto_argument', 'smooth_curveto_argument_sequence',
        'smooth_curveto_argument', 'quadratic_bezier_curveto_argument_sequence', 'quadratic_bezier_curveto_argument', 'smooth_quadratic_bezier_curveto_argument_sequence',
        'elliptical_arc_argument_sequence', 'elliptical_arc_argument', 'number',
    ];

    /* For tracing reduce actions, the names of all rules are required.*/
    /** @var array<string>  */
    static array $yyRuleName = [
        /*   0 */
        "svg_path ::= moveto_drawto_command_groups",
        /*   1 */
        "moveto_drawto_command_groups ::= moveto_drawto_command_groups moveto_drawto_command_group",
        /*   2 */
        "moveto_drawto_command_groups ::= moveto_drawto_command_group",
        /*   3 */
        "moveto_drawto_command_group ::= moveto drawto_commands",
        /*   4 */
        "drawto_commands ::= drawto_commands drawto_command",
        /*   5 */
        "drawto_commands ::= drawto_command",
        /*   6 */
        "drawto_command ::= closepath",
        /*   7 */
        "drawto_command ::= lineto",
        /*   8 */
        "drawto_command ::= horizontal_lineto",
        /*   9 */
        "drawto_command ::= vertical_lineto",
        /*  10 */
        "drawto_command ::= curveto",
        /*  11 */
        "drawto_command ::= smooth_curveto",
        /*  12 */
        "drawto_command ::= quadratic_bezier_curveto",
        /*  13 */
        "drawto_command ::= smooth_quadratic_bezier_curveto",
        /*  14 */
        "drawto_command ::= elliptical_arc",
        /*  15 */
        "moveto ::= MCMD moveto_argument_sequence",
        /*  16 */
        "moveto_argument_sequence ::= lineto_argument_sequence coordinate_pair",
        /*  17 */
        "moveto_argument_sequence ::= coordinate_pair",
        /*  18 */
        "closepath ::= ZCMD",
        /*  19 */
        "lineto ::= LCMD lineto_argument_sequence",
        /*  20 */
        "lineto_argument_sequence ::= lineto_argument_sequence coordinate_pair",
        /*  21 */
        "lineto_argument_sequence ::= coordinate_pair",
        /*  22 */
        "horizontal_lineto ::= HCMD horizontal_lineto_argument_sequence",
        /*  23 */
        "horizontal_lineto_argument_sequence ::= horizontal_lineto_argument_sequence coordinate",
        /*  24 */
        "horizontal_lineto_argument_sequence ::= coordinate",
        /*  25 */
        "vertical_lineto ::= VCMD vertical_lineto_argument_sequence",
        /*  26 */
        "vertical_lineto_argument_sequence ::= vertical_lineto_argument_sequence coordinate",
        /*  27 */
        "vertical_lineto_argument_sequence ::= coordinate",
        /*  28 */
        "curveto ::= CCMD curveto_argument_sequence",
        /*  29 */
        "curveto_argument_sequence ::= curveto_argument_sequence curveto_argument",
        /*  30 */
        "curveto_argument_sequence ::= curveto_argument",
        /*  31 */
        "curveto_argument ::= coordinate_pair coordinate_pair coordinate_pair",
        /*  32 */
        "smooth_curveto ::= SCMD smooth_curveto_argument_sequence",
        /*  33 */
        "smooth_curveto_argument_sequence ::= smooth_curveto_argument_sequence smooth_curveto_argument",
        /*  34 */
        "smooth_curveto_argument_sequence ::= smooth_curveto_argument",
        /*  35 */
        "smooth_curveto_argument ::= coordinate_pair coordinate_pair",
        /*  36 */
        "quadratic_bezier_curveto ::= QCMD quadratic_bezier_curveto_argument_sequence",
        /*  37 */
        "quadratic_bezier_curveto_argument_sequence ::= quadratic_bezier_curveto_argument_sequence quadratic_bezier_curveto_argument",
        /*  38 */
        "quadratic_bezier_curveto_argument_sequence ::= quadratic_bezier_curveto_argument",
        /*  39 */
        "quadratic_bezier_curveto_argument ::= coordinate_pair coordinate_pair",
        /*  40 */
        "smooth_quadratic_bezier_curveto ::= TCMD smooth_quadratic_bezier_curveto_argument_sequence",
        /*  41 */
        "smooth_quadratic_bezier_curveto_argument_sequence ::= smooth_quadratic_bezier_curveto_argument_sequence coordinate_pair",
        /*  42 */
        "smooth_quadratic_bezier_curveto_argument_sequence ::= coordinate_pair",
        /*  43 */
        "elliptical_arc ::= ACMD elliptical_arc_argument_sequence",
        /*  44 */
        "elliptical_arc_argument_sequence ::= elliptical_arc_argument_sequence elliptical_arc_argument",
        /*  45 */
        "elliptical_arc_argument_sequence ::= elliptical_arc_argument",
        /*  46 */
        "elliptical_arc_argument ::= POSNUM POSNUM number FLAG FLAG coordinate_pair",
        /*  47 */
        "coordinate_pair ::= coordinate coordinate",
        /*  48 */
        "coordinate ::= number",
        /*  49 */
        "number ::= POSNUM",
        /*  50 */
        "number ::= FLAG",
        /*  51 */
        "number ::= NEGNUM",
    ];

    /**
     ** This function returns the symbolic name associated with a token
     ** value.
     */
    public function SVGPathTokenName(string $tokenType): string
    {
        if (isset(self::$yyTokenName[$tokenType])) {
            return self::$yyTokenName[$tokenType];
        }
        return "Unknown";
    }

    /**
     * The following function deletes the value associated with a
     ** symbol.  The symbol can be either a terminal or nonterminal.
     ** "yymajor" is the symbol code, and "yypminor" is a pointer to
     ** the value.
     *
     * @param ?int $yymajor
     * @param ?JLexToken $yypminor
     * @return void
     */
    private function yy_destructor(?int $yymajor, $yypminor): void
    {
        switch ($yymajor) {
            /**
             **  Here is inserted the actions which take place when a
             ** terminal or non-terminal is destroyed.  This can happen
             ** when the symbol is popped from the stack during a
             ** reduce or during error processing or when a parser is
             ** being destroyed before it is finished parsing.
             **
             ** Note: during a reduce, the only symbols destroyed are those
             ** which appear on the RHS of the rule, but which are not used
             ** inside the C code.
             */
            default:
                break;   /* If no destructor action specified: do nothing */
        }
    }

    /**
     ** Pop the parser's stack once.
     **
     ** If there is a destructor routine associated with the token which
     ** is popped from the stack, then call it.
     **
     ** Return the major token number for the symbol popped.
     */
    private function yy_pop_parser_stack(): ?int
    {
        if ($this->yyidx < 0) return 0;
        $yytos = $this->yystack[$this->yyidx];
        if ($this->yyTraceFILE) {
            fprintf($this->yyTraceFILE, "%sPopping %s\n",
                $this->yyTracePrompt,
                self::$yyTokenName[$yytos->major]);
        }
        $this->yy_destructor($yytos->major, $yytos->minor);
        unset($this->yystack[$this->yyidx]);
        $this->yyidx--;
        return $yytos->major;
    }

    /**
     ** Deallocate and destroy a parser.  Destructors are all called for
     ** all stack elements before shutting the parser down.
     **
     ** Inputs:
     ** <ul>
     ** <li>  A pointer to the parser.  This should be a pointer
     **       obtained from SVGPathAlloc.
     ** <li>  A pointer to a function used to reclaim memory obtained
     **       from malloc.
     ** </ul>
     */
    public function __destruct()
    {
        while ($this->yyidx >= 0) {
            $this->yy_pop_parser_stack();
        }
    }

    /**
     ** Find the appropriate action for a parser given the terminal
     ** look-ahead token iLookAhead.
     **
     ** If the look-ahead token is YYNOCODE, then check to see if the action is
     ** independent of the look-ahead.  If it is, return the action, otherwise
     ** return YY_NO_ACTION.
     */
    private function yy_find_shift_action(
        int $iLookAhead     /* The look-ahead token */
    ): int
    {
        $i = 0;
        $stateno = $this->yystack[$this->yyidx]->stateno;

        if ($stateno > self::YY_SHIFT_MAX ||
            ($i = self::$yy_shift_ofst[$stateno]) === self::YY_SHIFT_USE_DFLT) {
            return self::$yy_default[$stateno];
        }
        if ($iLookAhead === self::YYNOCODE) {
            return $this->YY_NO_ACTION;
        }
        $i += $iLookAhead;
        if ($i < 0 || $i >= count(self::$yy_action) || self::$yy_lookahead[$i] != $iLookAhead) {
            if ($iLookAhead > 0) {
                if (isset(self::$yyFallback[$iLookAhead]) &&
                    ($iFallback = self::$yyFallback[$iLookAhead]) !== 0) {
                    if ($this->yyTraceFILE) {
                        fprintf($this->yyTraceFILE, "%sFALLBACK %s => %s\n",
                            $this->yyTracePrompt, self::$yyTokenName[$iLookAhead],
                            self::$yyTokenName[$iFallback]);
                    }
                    return $this->yy_find_shift_action($iFallback);
                }
                {
                    $j = $i - $iLookAhead + self::YYWILDCARD;
                    if ($j >= 0 && $j < count(self::$yy_action) && self::$yy_lookahead[$j] == self::YYWILDCARD) {
                        if ($this->yyTraceFILE) {
                            fprintf($this->yyTraceFILE, "%sWILDCARD %s => %s\n",
                                $this->yyTracePrompt, self::$yyTokenName[$iLookAhead],
                                self::$yyTokenName[self::YYWILDCARD]);
                        }
                        return self::$yy_action[$j];
                    }
                }
            }
            return self::$yy_default[$stateno];
        }
        return self::$yy_action[$i];
    }

    /*
** Find the appropriate action for a parser given the non-terminal
** look-ahead token iLookAhead.
**
** If the look-ahead token is YYNOCODE, then check to see if the action is
** independent of the look-ahead.  If it is, return the action, otherwise
** return YY_NO_ACTION.
*/
    private function yy_find_reduce_action(
        int $stateno,              /* Current state number */
        int $iLookAhead     /* The look-ahead token */
    ): int
    {
        $i = 0;

        if ($stateno > self::YY_REDUCE_MAX ||
            ($i = self::$yy_reduce_ofst[$stateno]) === self::YY_REDUCE_USE_DFLT) {
            return self::$yy_default[$stateno];
        }
        if ($iLookAhead === self::YYNOCODE) {
            return $this->YY_NO_ACTION;
        }
        $i += $iLookAhead;
        if ($i < 0 || $i >= count(self::$yy_action) || self::$yy_lookahead[$i] !== $iLookAhead) {
            return self::$yy_default[$stateno];
        }
        return self::$yy_action[$i];
    }

    /**
     ** Perform a shift action.
     *
     * @param int $yyNewState
     * @param int $yyMajor
     * @param mixed $yypMinor
     * @return void
     */
    private function yy_shift(
        int $yyNewState,               /* The new state to shift in */
        int $yyMajor,                  /* The major token to shift in */
        $yypMinor         /* Pointer ot the minor token to shift in */
    ): void
    {
        $this->yyidx++;
        if (isset($this->yystack[$this->yyidx])) {
            $yytos = $this->yystack[$this->yyidx];
        } else {
            $yytos = new SVGPathyyStackEntry;
            $this->yystack[$this->yyidx] = $yytos;
        }
        $yytos->stateno = $yyNewState;
        $yytos->major = $yyMajor;
        $yytos->minor = $yypMinor;
        if ($this->yyTraceFILE) {
            fprintf($this->yyTraceFILE, "%sShift %d\n", $this->yyTracePrompt, $yyNewState);
            fprintf($this->yyTraceFILE, "%sStack:", $this->yyTracePrompt);
            for ($i = 1; $i <= $this->yyidx; $i++) {
                $ent = $this->yystack[$i];
                fprintf($this->yyTraceFILE, " %s", self::$yyTokenName[$ent->major]);
            }
            fprintf($this->yyTraceFILE, "\n");
        }
    }

    /**
     * The following table contains information about every rule that
     ** is used during the reduce.
     ** Rather than pollute memory with a large number of arrays,
     ** we store both data points in the same array, indexing by
     ** rule number * 2.
     * static const struct {
     * YYCODETYPE lhs;         // Symbol on the left-hand side of the rule
     * unsigned char nrhs;     // Number of right-hand side symbols in the rule
     * } yyRuleInfo[] = {
     */

    /**
     * @var array<int>
     */
    static array $yyRuleInfo = [
        16, 1,
        17, 2,
        17, 1,
        18, 2,
        20, 2,
        20, 1,
        21, 1,
        21, 1,
        21, 1,
        21, 1,
        21, 1,
        21, 1,
        21, 1,
        21, 1,
        21, 1,
        19, 2,
        31, 2,
        31, 1,
        22, 1,
        23, 2,
        32, 2,
        32, 1,
        24, 2,
        34, 2,
        34, 1,
        25, 2,
        36, 2,
        36, 1,
        26, 2,
        37, 2,
        37, 1,
        38, 3,
        27, 2,
        39, 2,
        39, 1,
        40, 2,
        28, 2,
        41, 2,
        41, 1,
        42, 2,
        29, 2,
        43, 2,
        43, 1,
        30, 2,
        44, 2,
        44, 1,
        45, 6,
        33, 2,
        35, 1,
        46, 1,
        46, 1,
        46, 1,
    ];

    /*
** Perform a reduce action and the shift that must immediately
** follow the reduce.
*/
    private function yy_reduce(
        int $yyruleno                 /* Number of the rule by which to reduce */
    ): void
    {
        $yygoto = 0;                     /* The next state */
        $yyact = 0;                      /* The next action */
        /** @var array|null $yygotominor */
        $yygotominor = null;        /* The LHS of the rule reduced */
        $yymsp = null;            /* The top of the parser's stack */
        $yysize = 0;                     /* Amount to pop the stack */

        $yymsp = $this->yystack[$this->yyidx];
        if ($this->yyTraceFILE && isset(self::$yyRuleName[$yyruleno])) {
            fprintf($this->yyTraceFILE, "%sReduce [%s].\n", $this->yyTracePrompt,
                self::$yyRuleName[$yyruleno]);
        }

        switch ($yyruleno) {
            /* Beginning here are the reduction cases.  A typical example
  ** follows:
  **   case 0:
  **  #line <lineno> <grammarfile>
  **     { ... }           // User supplied code
  **  #line <lineno> <thisfile>
  **     break;
  */
            case 15:
#line 26 "svg-path.y"
                {
                    if (count($this->yystack[$this->yyidx + 0]->minor) === 2) {
                        $this->commands[] = array_merge(array($this->yystack[$this->yyidx + -1]->minor), $this->yystack[$this->yyidx + 0]->minor);
                    } else {
                        if ($this->yystack[$this->yyidx + -1]->minor->value === 'm') {
                            $arr = array('value' => 'l');
                        } else {
                            $arr = array('value' => 'L');
                        }
                        $c = array_splice($this->yystack[$this->yyidx + 0]->minor, 2);
                        $this->commands[] = array_merge(array($this->yystack[$this->yyidx + -1]->minor), $this->yystack[$this->yyidx + 0]->minor);
                        $this->commands[] = array_merge(array($arr), $c);
                    }
                }
#line 604 "svg-path.php"
                break;
            case 16:
            case 20:
            case 29:
            case 33:
            case 35:
            case 37:
            case 39:
            case 41:
            case 44:
#line 42 "svg-path.y"
                {
                    $yygotominor = array_merge($this->yystack[$this->yyidx + -1]->minor, $this->yystack[$this->yyidx + 0]->minor);
                }
#line 617 "svg-path.php"
                break;
            case 17:
            case 21:
            case 30:
            case 34:
            case 38:
            case 42:
            case 45:
            case 48:
            case 49:
            case 50:
            case 51:
#line 43 "svg-path.y"
                {
                    $yygotominor = $this->yystack[$this->yyidx + 0]->minor;
                }
#line 632 "svg-path.php"
                break;
            case 18:
#line 45 "svg-path.y"
                {
                    $this->commands[] = array($this->yystack[$this->yyidx + 0]->minor);
                }
#line 637 "svg-path.php"
                break;
            case 19:
            case 22:
            case 25:
            case 28:
            case 32:
            case 36:
            case 40:
            case 43:
#line 48 "svg-path.y"
                {
                    $this->commands[] = array_merge(array($this->yystack[$this->yyidx + -1]->minor), $this->yystack[$this->yyidx + 0]->minor);
                }
#line 649 "svg-path.php"
                break;
            case 23:
            case 26:
#line 59 "svg-path.y"
                {
                    $yygotominor = array_merge($this->yystack[$this->yyidx + -1]->minor, array($this->yystack[$this->yyidx + 0]->minor));
                }
#line 655 "svg-path.php"
                break;
            case 24:
            case 27:
#line 60 "svg-path.y"
                {
                    $yygotominor = array($this->yystack[$this->yyidx + 0]->minor);
                }
#line 661 "svg-path.php"
                break;
            case 31:
#line 80 "svg-path.y"
                {
                    $yygotominor = array_merge(
                        $this->yystack[$this->yyidx + -2]->minor,
                        $this->yystack[$this->yyidx + -1]->minor,
                        $this->yystack[$this->yyidx + 0]->minor
                    );
                }
#line 666 "svg-path.php"
                break;
            case 46:
#line 131 "svg-path.y"
                {
                    $yygotominor = array_merge(
                        [
                            $this->yystack[$this->yyidx + -5]->minor,
                            $this->yystack[$this->yyidx + -4]->minor,
                            $this->yystack[$this->yyidx + -3]->minor,
                            $this->yystack[$this->yyidx + -2]->minor,
                            $this->yystack[$this->yyidx + -1]->minor
                        ], $this->yystack[$this->yyidx + 0]->minor
                    );
                }
#line 671 "svg-path.php"
                break;
            case 47:
#line 133 "svg-path.y"
                {
                    $yygotominor = array($this->yystack[$this->yyidx + -1]->minor, $this->yystack[$this->yyidx + 0]->minor);
                }
#line 676 "svg-path.php"
                break;
        };
        $yygoto = self::$yyRuleInfo[2 * $yyruleno];
        $yysize = self::$yyRuleInfo[(2 * $yyruleno) + 1];

        $state_for_reduce = $this->yystack[$this->yyidx - $yysize]->stateno;

        $this->yyidx -= $yysize;
        $yyact = $this->yy_find_reduce_action($state_for_reduce, $yygoto);
        if ($yyact < self::YYNSTATE) {
            $this->yy_shift($yyact, $yygoto, $yygotominor);
        } else if ($yyact == self::YYNSTATE + self::YYNRULE + 1) {
            $this->yy_accept();
        }
    }

    /*
** The following code executes when the parse fails
*/
    private function yy_parse_failed(): void
    {
        if ($this->yyTraceFILE) {
            fprintf($this->yyTraceFILE, "%sFail!\n", $this->yyTracePrompt);
        }
        while ($this->yyidx >= 0) $this->yy_pop_parser_stack();
        /* Here code is inserted which will be executed whenever the
  ** parser fails */
    }

    /**
     ** The following code executes when a syntax error first occurs.
     *
     * @param int $yymajor
     * @param null|JLexToken $yyminor
     * @return void
     */
    private function yy_syntax_error(
        int $yymajor,                   /* The major type of the error token */
            $yyminor            /* The minor type of the error token */
    ): void
    {
    }

    /**
     ** The following is executed when the parser accepts
     */
    private function yy_accept(): void
    {
        if ($this->yyTraceFILE) {
            fprintf($this->yyTraceFILE, "%sAccept!\n", $this->yyTracePrompt);
        }
        while ($this->yyidx >= 0) $this->yy_pop_parser_stack();
        /**
         ** Here code is inserted which will be executed whenever the
         ** parser accepts
         **/
    }

    /**
     * The main parser program.
     ** The first argument is a pointer to a structure obtained from
     ** "SVGPathAlloc" which describes the current state of the parser.
     ** The second argument is the major token number.  The third is
     ** the minor token.  The fourth optional argument is whatever the
     ** user wants (and specified in the grammar) and is available for
     ** use by the action routines.
     **
     ** Inputs:
     ** <ul>
     ** <li> A pointer to the parser (an opaque structure.)
     ** <li> The major token number.
     ** <li> The minor token number.
     ** <li> An option argument of a grammar-specified type.
     ** </ul>
     **
     ** Outputs:
     ** None.
     */
    /**
     * @param int $yymajor
     * @param null|JLexToken $yyminor
     * @return void
     */
    function SVGPath(
        $yymajor,                 /* The major token code number */
        $yyminor = null           /* The value for the token */
    ): void
    {
        $yyact = 0;            /* The parser action. */
        $yyendofinput = 0;     /* True if we are at the end of input */
        $yyerrorhit = 0;   /* True if yymajor has invoked an error */

        /* (re)initialize the parser, if necessary */
        if ($this->yyidx < 0) {
            $this->yyidx = 0;
            $this->yyerrcnt = -1;
            $ent = new SVGPathyyStackEntry;
            $ent->stateno = 0;
            $ent->major = 0;
            $this->yystack = [0 => $ent];

            $this->YY_NO_ACTION = self::YYNSTATE + self::YYNRULE + 2;
            $this->YY_ACCEPT_ACTION = self::YYNSTATE + self::YYNRULE + 1;
            $this->YY_ERROR_ACTION = self::YYNSTATE + self::YYNRULE;
        }
        $yyendofinput = ($yymajor === 0);

        if ($this->yyTraceFILE) {
            fprintf($this->yyTraceFILE, "%sInput %s\n", $this->yyTracePrompt,
                self::$yyTokenName[$yymajor]);
        }

        do {
            $yyact = $this->yy_find_shift_action($yymajor);
            if ($yyact < self::YYNSTATE) {
                $this->yy_shift($yyact, $yymajor, $yyminor);
                $this->yyerrcnt--;
                if ($yyendofinput && $this->yyidx >= 0) {
                    $yymajor = 0;
                } else {
                    $yymajor = self::YYNOCODE;
                }
            } else if ($yyact < self::YYNSTATE + self::YYNRULE) {
                $this->yy_reduce($yyact - self::YYNSTATE);
            } else if ($yyact === $this->YY_ERROR_ACTION) {
                if ($this->yyTraceFILE) {
                    fprintf($this->yyTraceFILE, "%sSyntax Error!\n", $this->yyTracePrompt);
                }
                /** @phpstan-ignore-next-line */
                if (self::YYERRORSYMBOL) {
                    /** A syntax error has occurred.
                     ** The response to an error depends upon whether or not the
                     ** grammar defines an error token "ERROR".
                     **
                     ** This is what we do if the grammar does define ERROR:
                     **
                     **  * Call the %syntax_error function.
                     **
                     **  * Begin popping the stack until we enter a state where
                     **    it is legal to shift the error symbol, then shift
                     **    the error symbol.
                     **
                     **  * Set the error count to three.
                     **
                     **  * Begin accepting and shifting new tokens.  No new error
                     **    processing will occur until three tokens have been
                     **    shifted successfully.
                     **
                     */
                    if ($this->yyerrcnt < 0) {
                        $this->yy_syntax_error($yymajor, $yyminor);
                    }
                    $yymx = $this->yystack[$this->yyidx]->major;
                    if ($yymx === self::YYERRORSYMBOL || $yyerrorhit) {
                        if ($this->yyTraceFILE) {
                            fprintf($this->yyTraceFILE, "%sDiscard input token %s\n",
                                $this->yyTracePrompt, self::$yyTokenName[$yymajor]);
                        }
                        $this->yy_destructor($yymajor, $yyminor);
                        $yymajor = self::YYNOCODE;
                    } else {
                        while (
                            $this->yyidx >= 0 &&
                            $yymx !== self::YYERRORSYMBOL &&
                            ($yyact = $this->yy_find_reduce_action(
                                $this->yystack[$this->yyidx]->stateno,
                                self::YYERRORSYMBOL)) >= self::YYNSTATE
                        ) {
                            $this->yy_pop_parser_stack();
                        }
                        if ($this->yyidx < 0 || $yymajor === 0) {
                            $this->yy_destructor($yymajor, $yyminor);
                            $this->yy_parse_failed();
                            $yymajor = self::YYNOCODE;
                        } else if ($yymx !== self::YYERRORSYMBOL) {
                            $this->yy_shift($yyact, self::YYERRORSYMBOL, 0);
                        }
                    }
                    $this->yyerrcnt = 3;
                    $yyerrorhit = 1;
                } else {  /* YYERRORSYMBOL is not defined */
                    /**
                     **  This is what we do if the grammar does not define ERROR:
                     **
                     **  * Report an error message, and throw away the input token.
                     **
                     **  * If the input token is $, then fail the parse.
                     **
                     ** As before, subsequent error messages are suppressed until
                     ** three input tokens have been successfully shifted.
                     */
                    if ($this->yyerrcnt <= 0) {
                        $this->yy_syntax_error($yymajor, $yyminor);
                    }
                    $this->yyerrcnt = 3;
                    $this->yy_destructor($yymajor, $yyminor);
                    if ($yyendofinput) {
                        $this->yy_parse_failed();
                    }
                    $yymajor = self::YYNOCODE;
                }
            } else {
                $this->yy_accept();
                $yymajor = self::YYNOCODE;
            }
        } while ($yymajor !== self::YYNOCODE && $this->yyidx >= 0);
    }
}
