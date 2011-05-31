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

// Named capturing group (Perl): (?<name>…).
%token  named_capturing_      \(\?<[^>]+>

// Non-capturing group.
%token  non_capturing_        \(\?:

// Capturing group: (…).
%token  capturing_            \(
%token _capturing             \)

// Quantifiers.
%token  zero_or_one           \?
%token  zero_or_more          \*
%token  one_or_more           \+
%token  exactly_n             \{[0-9]+\}
%token  at_least_n_or_more_m  \{[0-9]+,[0-9]+\}
%token  n_or_more             \{[0-9]+,\}

// Alternation.
%token alternation            \|

// Literal.
%token literal                .


// Rules.

#expression:
    alternation()

alternation:
    concatenation() ( ::alternation:: concatenation() #alternation )*

#concatenation:
    quantification()*

quantification:
    class()  ( quantifier() #quantification )?
  | simple() ( quantifier() #quantification )?

quantifier:
    <zero_or_one>
  | <zero_or_more>
  | <one_or_more>
  | <exactly_n>
  | <at_least_n_or_more_m>
  | <n_or_more>

#class:
    ( ::negative_class_:: #negativeclass <literal>+ )
  | ( ::class_:: ( range() | <literal> )+ )
    ::_class::

#range:
    <literal> <range> <literal>

simple:
    capturing()
  | <literal>

capturing:
    (
        <named_capturing_> #namedcapturing
      | ::non_capturing_::
      | ::capturing_:: #capturing
    )
    alternation() ::_capturing::
