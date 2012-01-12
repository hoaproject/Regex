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
-> import('Visitor.Visit')

/**
 * \Hoa\String\Unicode\Util
 */
-> import('String.Unicode.Util');

}

namespace Hoa\Regex\Visitor {

/**
 * Class \Hoa\Regex\Visitor\Isotropic.
 *
 * Isotropic walk on the AST to generate a data.
 *
 * @author     Ivan Enderlin <ivan.enderlin@hoa-project.net>
 * @copyright  Copyright © 2007-2012 Ivan Enderlin.
 * @license    New BSD License
 */

class Isotropic implements \Hoa\Visitor\Visit {

    /**
     * Numeric-sampler.
     *
     * @var \Hoa\Test\Sampler object
     */
    protected $_sampler = null;



    /**
     * Initialize numeric-sampler.
     *
     * @access  public
     * @param   \Hoa\Test\Sampler  $sampler    Numeric-sampler.
     * @return  void
     */
    public function __construct ( \Hoa\Test\Sampler $sampler ) {

        $this->_sampler = $sampler;

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

        switch($element->getId()) {

            case '#expression':
            case '#capturing':
            case '#namedcapturing':
                return $element->getChild(0)->accept($this, $handle, $eldnah);
              break;

            case '#alternation':
            case '#class':
                return $element->getChild($this->_sampler->getInteger(
                    0,
                    $element->getChildrenNumber() - 1
                ))->accept($this, $handle, $eldnah);
              break;

            case '#concatenation':
                $out = null;

                foreach($element->getChildren() as $child)
                    $out .= $child->accept($this, $handle, $eldnah);

                return $out;
              break;

            case '#quantification':
                $out = null;
                $xy  = $element->getChild(1)->getValueValue();
                $x   = 0;
                $y   = 0;

                switch($element->getChild(1)->getValueToken()) {

                    case 'zero_or_one':
                        $y = 1;
                      break;

                    case 'zero_or_more':
                        $y = 5; // why not?
                      break;

                    case 'one_or_more':
                        $x = 1;
                        $y = 5; // why not?
                      break;

                    case 'exactly_n':
                        $x = $y = (int) substr($xy, 1, -1);
                      break;

                    case 'n_to_m':
                        $xy = explode(',', substr($xy, 1, -1));
                        $x  = (int) trim($xy[0]);
                        $y  = (int) trim($xy[1]);
                      break;

                    case 'n_or_more':
                        $xy = explode(',', substr($xy, 1, -1));
                        $x  = (int) trim($xy[0]);
                        $y  = 5; // why not?
                      break;
                }

                for($i = 0, $max = $this->_sampler->getInteger($x, $y);
                    $i < $max; ++$i)
                    $out .= $element->getChild(0)->accept(
                        $this,
                        $handle,
                        $eldnah
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
                    ord($element->getChild(0)->getValueValue()),
                    ord($element->getChild(1)->getValueValue())
                ));
              break;

            case 'token':
                $value = $element->getValueValue();

                switch($element->getValueToken()) {

                    case 'character':
                        $value = ltrim($value, '\\');
                        switch($value) {

                            case 'a':
                                return "\a";

                            case 'e':
                                return "\e";

                            case 'f':
                                return "\f";

                            case 'n':
                                return "\n";

                            case 'r':
                                return "\r";

                            case 't':
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
                                return \Hoa\String\Unicode\Util::fromCode(
                                    hexdec($value)
                                );
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
                                $h = array(
                                    chr(0x0009),
                                    chr(0x0020),
                                    chr(0x00a0)
                                );

                                return $h[$this->_sampler->getInteger(
                                    0,
                                    count($h) -1
                                )];

                            case 'v':
                                $v = array(
                                    chr(0x000a),
                                    chr(0x000b),
                                    chr(0x000c),
                                    chr(0x000d)
                                );

                                return $v[$this->_sampler->getInteger(
                                    0,
                                    count($v) -1
                                )];

                            case 'w':
                                $w  = array_merge(
                                    range(0x41, 0x5a),
                                    range(0x61, 0x7a),
                                    array(0x5f)
                                );

                                return chr($w[
                                    $this->_sampler->getInteger(
                                        0,
                                        count($w) - 1
                                    )
                                ]);

                            default:
                                return '?';
                        }
                      break;

                    case 'literal':
                        return str_replace('\\', '', $element->getValueValue());
                }

              break;
        }

        return;
    }
}

}
