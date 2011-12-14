<?php

/**
 * Hoa
 *
 *
 * @license
 *
 * New BSD License
 *
 * Copyright © 2007-2011, Ivan Enderlin. All rights reserved.
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
 * …
 *
 * @author     Ivan Enderlin <ivan.enderlin@hoa-project.net>
 * @copyright  Copyright © 2007-2011 Ivan Enderlin.
 * @license    New BSD License
 */

class Uniform implements \Hoa\Visitor\Visit {

    protected $_sampler = null;
    protected $_n       = 0;

    public function __construct ( \Hoa\Test\Sampler $sampler, $n ) {

        $this->_sampler = $sampler;
        $this->_n       = $n;

        return;
    }

    public function visit ( \Hoa\Visitor\Element $element,
                            &$handle = null, $eldnah = null ) {

        $n    = null === $eldnah ? $this->_n : $eldnah;
        $data = $element->getData();

        if(0 == $computed = $data['precompute']['n'])
            return null;

        switch($element->getId()) {

            case '#expression':
            case '#capturing':
            case '#namedcapturing':
                return $element->getChild(0)->accept($this, $handle, $n);
              break;

            case '#alternation':
                $stat = array();

                foreach($element->getChildren() as $c => $child) {

                    $handle   = $child->getData();
                    $stat[$c] = $handle['precompute']['n'];
                }

                $i = $this->_sampler->getInteger(1, $computed);

                for($e = 0, $b = $stat[$e], $max = count($stat);
                    $e < $max - 1 && $i > $b;
                    $b += $stat[++$e]);

                return $element->getChild($e)->accept($this, $handle, $n);
              break;

            case '#concatenation':
                $out = null;

                foreach($element->getChildren() as $child)
                    $out .= $child->accept($this, $handle, $n);

                return $out;
              break;

            case 'token':
                return str_replace('\\', '', $element->getValueValue());
              break;
        }

        return;
    }
}

}
