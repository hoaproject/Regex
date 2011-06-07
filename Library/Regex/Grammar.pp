// Grammar of PCRE.
// You should read this with \Hoa\Compiler\Llk::load().
//
// More informations at http://pcre.org/pcre.txt, sections pcrepattern &
// pcresyntax.


// Character classes.
%token  negative_class_       \[\^
%token  class_                \[
%token _class                 \]
%token  range                 -

// Lookahead and lookbehind assertions.
%token  lookahead_            \(\?=
%token  negative_lookahead_   \(\?!
%token  lookbehind_           \(\?<=
%token  negative_lookbehind_  \(\?<!

// Named capturing group (Perl): (?<name>…).
%token  named_capturing_      \(\?<              -> nc
%token  nc:capturing_name     [^>]+
%token  nc:_named_capturing   >                  -> default

// Non-capturing group.
%token  non_capturing_        \(\?:

// Conditions.
%token  named_reference_      \(\?\(<            -> nc
%token  relative_reference_   \(\?\((?=[\+\-])   -> c
%token  absolute_reference_   \(\?\((?=\d)       -> c
%token  c:index               [\+\-]?\d+         -> default
%token  assertion_reference_  \(\?\(

// Capturing group: (…).
%token  capturing_            \(
%token _capturing             \)

// Quantifiers (by default, greedy).
%token  zero_or_one_possessive   \?\+
%token  zero_or_one_lazy         \?\?
%token  zero_or_one              \?
%token  zero_or_more_possessive  \*\+
%token  zero_or_more_lazy        \*\?
%token  zero_or_more             \*
%token  one_or_more_possessive   \+\+
%token  one_or_more_lazy         \+\?
%token  one_or_more              \+
%token  exactly_n                \{[0-9]+\}
%token  n_to_m_possessive        \{[0-9]+,[0-9]+\}\+
%token  n_to_m_lazy              \{[0-9]+,[0-9]+\}\?
%token  n_to_m                   \{[0-9]+,[0-9]+\}
%token  n_or_more_possessive     \{[0-9]+,\}\+
%token  n_or_more_lazy           \{[0-9]+,\}\?
%token  n_or_more                \{[0-9]+,\}

// Alternation.
%token alternation            \|

// Literal.
%token character                 \\([aefnrt]|c[\x00-\x7f])
%token dynamic_character         \\([0-7]{,3}|x[0-9a-zA-Z]{,2}|x{[0-9a-zA-Z]+})
// Please, see PCRESYNTAX(3), General Category properties, PCRE special category
// properties and script names for \p{} and \P{}.
%token character_type            \\([CdDhHNRsSvVwWX]|[pP]{[^}]+})
%token anchor                    \\(bBAZzG)|\^|\$
%token match_point_reset         \\K
%token literal                   \\.|.


// Rules.

#expression:
    alternation()

alternation:
    concatenation() ( ::alternation:: concatenation() #alternation )*

concatenation:
    condition() ( condition() #concatenation )*
  | (   assertion() | quantification() )
    ( ( assertion() | quantification() ) #concatenation )*

#condition:
    (
        ::named_reference_:: <capturing_name> ::_named_capturing:: #namedcondition
      | (
            ::relative_reference_:: #relativecondition
          | ::absolute_reference_:: #absolutecondition
        )
        <index>
      | ::assertion_reference_:: alternation() #assertioncondition
    )
    ::_capturing:: concatenation()?
    ( ::alternation:: concatenation()? )?
    ::_capturing::

assertion:
    (
        ::lookahead_::           #lookahead
      | ::negative_lookahead_::  #negativelookahead
      | ::lookbehind_::          #lookbehind
      | ::negative_lookbehind_:: #negativelookbehind
    )
    alternation() ::_capturing::

quantification:
    ( class() | simple() )  ( quantifier() #quantification )?

quantifier:
    <zero_or_one_possessive>  | <zero_or_one_lazy>  | <zero_or_one>
  | <zero_or_more_possessive> | <zero_or_more_lazy> | <zero_or_more>
  | <one_or_more_possessive>  | <one_or_more_lazy>  | <one_or_more>
  | <exactly_n>
  | <n_to_m_possessive>       | <n_to_m_lazy>       | <n_to_m>
  | <n_or_more_possessive>    | <n_or_more_lazy>    | <n_or_more>

#class:
    (
        ::negative_class_:: #negativeclass
      | ::class_::
    )
    ( range() | literal() )+
    ::_class::

#range:
    literal() ::range:: literal()

simple:
    capturing()
  | literal()

#capturing:
    (
        ::named_capturing_:: <capturing_name> ::_named_capturing:: #namedcapturing
      | ::non_capturing_:: #noncapturing
      | ::capturing_::
    )
    alternation() ::_capturing::

literal:
    <character>
  | <dynamic_character>
  | <character_type>
  | <anchor>
  | <match_point_reset>
  | <literal>
