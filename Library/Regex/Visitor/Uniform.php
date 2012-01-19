<?php

/**
 * Hoa
 *
 *
 * @license
 *
 * New BSD License
 *
 * Copyright © 2007-2012, Ivan Enderlin. All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 *     * Redistributions of source code must retain the above copyright
 *       notice, this list of conditions and the following disclaimer.
 *     * Redistributions in binary form must reproduce the above copyright
 *       notice, this list of conditions and the following disclaimer in the
 *       documentation and/or other materials provided with the distribution.
 *     * Neither the name of the Hoa nor the names of its contributors may be
 *       used to endorse or promote products derived from this software without
 *       specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS"
 * AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE
 * IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE
 * ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT HOLDERS AND CONTRIBUTORS BE
 * LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR
 * CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF
 * SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS
 * INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN
 * CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
 * ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
 * POSSIBILITY OF SUCH DAMAGE.
 */

namespace {

from('Hoa')

/**
 * \Hoa\Regex\Visitor\Exception
 */
-> import('Regex.Visitor.Exception')

/**
 * \Hoa\Visitor\Visit
 */
-> import('Visitor.Visit');

}

namespace Hoa\Regex\Visitor {

/**
 * Class \Hoa\Regex\Visitor\Uniform.
 *
 * Generate a data of size n that can be matched by a PCRE.
 *
 * @author     Ivan Enderlin <ivan.enderlin@hoa-project.net>
 * @copyright  Copyright © 2007-2012 Ivan Enderlin.
 * @license    New BSD License
 */

class Uniform implements \Hoa\Visitor\Visit {

    /**
     * Numeric-sampler.
     *
     * @var \Hoa\Test\Sampler object
     */
    protected $_sampler = null;

    /**
     * Given size: n.
     *
     * @var \Hoa\Regex\Visitor\Uniform int
     */
    protected $_n       = 0;

    protected static $_hSpaces = null;
    protected static $_vSpaces = null;



    /**
     * Initialize numeric-sampler and the size.
     *
     * @access  public
     * @param   \Hoa\Test\Sampler  $sampler    Numeric-sampler.
     * @param   int                $n          Size.
     * @return  void
     */
    public function __construct ( \Hoa\Test\Sampler $sampler, $n = 0 ) {

        $this->_sampler = $sampler;
        $this->setSize($n);

        if(null === self::$_hSpaces)
            self::$_hSpaces = array(
                $this->uni_chr(0x0009), // horizontal tab
                $this->uni_chr(0x0020), // space
                $this->uni_chr(0x00a0), // non-break space
                $this->uni_chr(0x1680), // ogham space mark
                $this->uni_chr(0x180e), // mongolian vowel separator
                $this->uni_chr(0x2000), // en quad
                $this->uni_chr(0x2001), // em quad
                $this->uni_chr(0x2002), // en space
                $this->uni_chr(0x2003), // em space
                $this->uni_chr(0x2004), // three-per-em space
                $this->uni_chr(0x2005), // four-per-em space
                $this->uni_chr(0x2006), // six-per-em space
                $this->uni_chr(0x2007), // figure space
                $this->uni_chr(0x2008), // punctuation space
                $this->uni_chr(0x2009), // thin space
                $this->uni_chr(0x200a), // hair space
                $this->uni_chr(0x202f), // narow no-break space
                $this->uni_chr(0x205f), // mediaum mathematical space
                $this->uni_chr(0x3000)  // ideographic space
            );

        if(null === self::$_vSpaces)
            self::$_vSpaces = array(
                $this->uni_chr(0x000a), // linefeed
                $this->uni_chr(0x000b), // vertical tab
                $this->uni_chr(0x000c), // formfeed
                $this->uni_chr(0x000d), // carriage return
                $this->uni_chr(0x0085), // next line
                $this->uni_chr(0x2028), // line separator
                $this->uni_chr(0x2029)  // paragraph separator
            );

        return;
    }

    /**
     * Visit an element.
     *
     * @access  public
     * @param   \Hoa\Visitor\Element  $element    Element to visit.
     * @param   mixed                 &$handle    Handle (reference).
     * @param   mixed                 $eldnah     Handle (not reference).
     * @return  mixed
     */
    public function visit ( \Hoa\Visitor\Element $element,
                            &$handle = null, $eldnah = null ) {

        $n    = null === $eldnah ? $this->_n : $eldnah;
        $data = $element->getData();

        if(0 == $computed = $data['precompute'][$n]['n'])
            return null;

        switch($element->getId()) {

            case '#expression':
            case '#capturing':
            case '#namedcapturing':
                return $element->getChild(0)->accept($this, $handle, $n);
              break;

            case '#alternation':
            case '#class':
                $stat = array();

                foreach($element->getChildren() as $c => $child) {

                    $foo      = $child->getData();
                    $stat[$c] = $foo['precompute'][$n]['n'];
                }

                $i = $this->_sampler->getInteger(1, $computed);

                for($e = 0, $b = $stat[$e], $max = count($stat);
                    $e < $max - 1 && $i > $b;
                    $b += $stat[++$e]);

                return $element->getChild($e)->accept($this, $handle, $n);
              break;

            case '#concatenation':
                $out      = null;
                $Γ        = $data['precompute'][$n]['Γ'];
                $γ        = $Γ[$this->_sampler->getInteger(0, count($Γ) - 1)];

                foreach($element->getChildren() as $i => $child)
                    $out .= $child->accept($this, $handle, $γ[$i]);

                return $out;
              break;

            case '#quantification':
                $out  = null;
                $stat = $data['precompute'][$n]['xy'];
                $i    = $this->_sampler->getInteger(1, $computed);
                $b    = 0;
                $x    = key($stat);

                foreach($stat as $α => $st)
                    if($i <= $b += $st['n'])
                        break;

                for($j = 0; $j < $α; ++$j)
                    $out .= $element->getChild(0)->accept(
                        $this,
                        $handle,
                        $st['Γ'][$j]
                    );

                return $out;
              break;

            case '#negativeclass':
                $c = array();

                foreach($element->getChildren() as $child)
                    $c[ord($child->accept($this, $handle, $eldnah))] = true;

                do {

                    // all printable ASCII.
                    $i = $this->_sampler->getInteger(32, 126);
                } while(isset($c[$i]));

                return chr($i);
              break;

            case '#range':
                $out = null;

                return chr($this->_sampler->getInteger(
                    ord($element->getChild(0)->accept($this, $handle, $eldnah)),
                    ord($element->getChild(1)->accept($this, $handle, $eldnah))
                ));
              break;

            case 'token':
                $value = $element->getValueValue();

                switch($element->getValueToken()) {

                    case 'character':
                        switch($value) {

                            case '\a':
                                return "\a";

                            case '\e':
                                return "\e";

                            case '\f':
                                return "\f";

                            case '\n':
                                return "\n";

                            case '\r':
                                return "\r";

                            case '\t':
                                return "\t";

                            default:
                                return chr($value[2]);
                        }
                      break;

                    case 'dynamic_character':
                        $value = ltrim($value, '\\');

                        switch($value[0]) {

                            case 'x':
                                $value = trim($value, 'x{}');
                                return $this->uni_chr($value);
                              break;

                            default:
                                return chr(octdec($value));
                        }
                      break;

                    case 'character_type':
                        $value = ltrim($value, '\\');

                        switch($value) {

                            case 'C':
                                return $this->_sampler->getInteger(0, 127);

                            case 'd':
                                return $this->_sampler->getInteger(0, 9);

                            case 's':
                                $value = $this->_sampler->getInteger(0, 1)
                                             ? 'h'
                                             : 'v';

                            case 'h':
                                return static::$_hSpaces[
                                    $this->_sampler->getInteger(
                                        0,
                                        count(static::$_hSpaces) - 1
                                    )
                                ];

                            case 'v':
                                return static::$_vSpaces[
                                    $this->_sampler->getInteger(
                                        0,
                                        count(static::$_vSpaces) - 1
                                    )
                                ];

                            case 'w':
                                $_  = array_merge(
                                    range(0x41, 0x5a),
                                    range(0x61, 0x7a),
                                    array(0x5f)
                                );

                                return $this->uni_chr(dechex($_[
                                    $this->_sampler->getInteger(
                                        0,
                                        count($_) - 1
                                    )
                                ]));

                            default:
                                return '?';
                        }
                      break;

                    case 'literal':
                        return str_replace('\\\\', '\\', preg_replace(
                            '#\\\(?!\\\)#',
                            '',
                            $element->getValueValue()
                        ));
                }
              break;
        }

        return -1;
    }

    /**
     * Set size.
     *
     * @access  public
     * @param   int  $n    Size.
     * @return  int
     */
    public function setSize ( $n ) {

        $old      = $this->_n;
        $this->_n = $n;

        return $old;
    }

    /**
     * Get size.
     *
     * @access  public
     * @return  int
     */
    public function getSize ( ) {

        return $this->_n;
    }

    public function uni_chr ( $hexa ) {

        return mb_convert_encoding(
            '&#' . hexdec($hexa) . ';',
            'UTF-8',
            'HTML-ENTITIES'
        );
    }
}

}
