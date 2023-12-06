<?php
/**
 * PHP CS Fixer config.
 */
$finder = PhpCsFixer\Finder::create()
    ->in(__DIR__.'/src')
    ->name('*.php')
    ->append([__FILE__])
;

return (new PhpCsFixer\Config())
    ->setRules([
        'array_indentation' => true,
        'array_syntax' => ['syntax' => 'short'],
        'binary_operator_spaces' => true,
        'blank_line_after_namespace' => true,
        'blank_line_after_opening_tag' => true,
        'blank_line_before_statement' => true,
        'braces' => true,
        'cast_spaces' => true,
        'class_attributes_separation' => true,
        'class_definition' => true,
        'class_reference_name_casing' => true,
        'clean_namespace' => true,
        'constant_case' => true,
        'combine_consecutive_issets' => true,
        'combine_consecutive_unsets' => true,
        'compact_nullable_typehint' => true,
        'concat_space' => true,
        'control_structure_continuation_position' => true,
        'declare_equal_normalize' => true,
        'dir_constant' => true,
        'doctrine_annotation_array_assignment' => true,
        'doctrine_annotation_braces' => true,
        'doctrine_annotation_indentation' => true,
        'doctrine_annotation_spaces' => false, // broken
        'elseif' => true,
        'encoding' => true,
        'escape_implicit_backslashes' => true,
        'explicit_indirect_variable' => true,
        'explicit_string_variable' => true,
        'full_opening_tag' => true,
        'fully_qualified_strict_types' => true,
        'function_declaration' => true,
        'function_to_constant' => true,
        'function_typehint_space' => true,
        'general_phpdoc_tag_rename' => true,
        'get_class_to_class_keyword' => true,
        'heredoc_to_nowdoc' => true,
        'include' => true,
        'increment_style' => ['style' => 'post'], // Differs from symfony
        'indentation_type' => true,
        'integer_literal_case' => true,
        'is_null' => true,
        'lambda_not_used_import' => true,
        'line_ending' => true,
        'linebreak_after_opening_tag' => true,
        'list_syntax' => ['syntax' => 'short'],
        'logical_operators' => true,
        'lowercase_cast' => true,
        'lowercase_keywords' => true,
        'lowercase_static_reference' => true,
        'magic_constant_casing' => true,
        'method_argument_space' => true,
        // strval($f) is better than ((string)$f)
        // when outter parenthese are required
        'modernize_types_casting' => false,
        'multiline_comment_opening_closing' => true,
        'multiline_whitespace_before_semicolons' => ['strategy' => 'new_line_for_chained_calls'],
        'native_function_casing' => true,
        'native_function_type_declaration_casing' => true,
        'new_with_braces' => true,
        'no_alias_functions' => true,
        'no_alias_language_construct_call' => true,
        'no_alternative_syntax' => true,
        'no_binary_string' => true,
        'no_blank_lines_after_class_opening' => true,
        'no_blank_lines_after_phpdoc' => true,
        'no_break_comment' => true,
        'no_closing_tag' => true,
        'no_empty_comment' => true,
        'no_empty_phpdoc' => true,
        'no_empty_statement' => true,
        'no_extra_blank_lines' => true,
        'no_homoglyph_names' => true,
        'no_leading_import_slash' => true,
        'no_leading_namespace_whitespace' => true,
        'no_mixed_echo_print' => true,
        // This prevents line breaks after => in arrow functions
        'no_multiline_whitespace_around_double_arrow' => false,
        'no_php4_constructor' => true,
        'no_short_bool_cast' => true,
        // This prevents code like this:
        // FooBuilder
        //     ::create()
        //     ->foo()
        //     ->bar();
        'no_space_around_double_colon' => false,
        'no_singleline_whitespace_before_semicolons' => true,
        'no_spaces_after_function_name' => true,
        'no_spaces_around_offset' => true,
        'no_spaces_inside_parenthesis' => true,
        'no_superfluous_elseif' => true,
        'no_superfluous_phpdoc_tags' => false,
        'no_trailing_comma_in_list_call' => true,
        'no_trailing_comma_in_singleline_array' => true,
        'no_trailing_whitespace' => true,
        'no_trailing_whitespace_in_comment' => true,
        'no_unneeded_control_parentheses' => true,
        'no_unneeded_curly_braces' => true,
        'no_unneeded_final_method' => true,
        'no_unneeded_import_alias' => true,
        'no_unused_imports' => true,
        'no_useless_else' => true,
        'no_useless_return' => true,
        'no_whitespace_before_comma_in_array' => true,
        'no_whitespace_in_blank_line' => true,
        'non_printable_character' => ['use_escape_sequences_in_strings' => true],
        'normalize_index_brace' => true,
        'object_operator_without_whitespace' => true,
        'octal_notation' => true,
        // 'operator_linebreak' => true, // TODO
        'ordered_imports' => true,
        'php_unit_construct' => true,
        'php_unit_dedicate_assert' => true,
        'php_unit_expectation' => true,
        'php_unit_fqcn_annotation' => true,
        'php_unit_method_casing' => true,
        'php_unit_mock' => true,
        'php_unit_namespaced' => false, // incompleted / bogus
        'php_unit_no_expectation_annotation' => true,
        'php_unit_set_up_tear_down_visibility' => true,
        'php_unit_test_annotation' => [
            'style' => 'prefix',
        ],
        'php_unit_test_case_static_method_calls' => [
            'call_type' => 'self',
        ],
        'phpdoc_align' => true,
        'phpdoc_annotation_without_dot' => true,
        'phpdoc_indent' => true,
        'phpdoc_inline_tag_normalizer' => true,
        'phpdoc_no_access' => true,
        'phpdoc_no_alias_tag' => true,
        // This removes '@return void', which we don't want to remove.
        'phpdoc_no_empty_return' => false,
        'phpdoc_no_package' => true,
        'phpdoc_no_useless_inheritdoc' => true,
        'phpdoc_order' => true,
        'phpdoc_order_by_value' => true,
        'phpdoc_return_self_reference' => true,
        'phpdoc_scalar' => true,
        'phpdoc_separation' => true,
        'phpdoc_single_line_var_spacing' => true,
        'phpdoc_summary' => true,
        'phpdoc_to_comment' => true,
        'phpdoc_trim' => true,
        'phpdoc_trim_consecutive_blank_line_separation' => true,
        'phpdoc_types' => true,
        'phpdoc_types_order' => false, // puts null before the type
        'phpdoc_var_without_name' => false, // breaks `* @var array Table indexed analogously to $action.` to `* @var array Table indexed analogously to.`
        'psr_autoloading' => true,
        'return_assignment' => false,
        'return_type_declaration' => true,
        'self_accessor' => true,
        'self_static_accessor' => true,
        'semicolon_after_instruction' => true,
        'set_type_to_cast' => true,
        'short_scalar_cast' => true,
        'single_blank_line_at_eof' => true,
        'single_blank_line_before_namespace' => true,
        'single_class_element_per_statement' => true,
        'single_import_per_statement' => true,
        'single_line_after_imports' => true,
        'single_line_comment_style' => true,
        'single_quote' => true,
        'single_space_after_construct' => true,
        'single_trait_insert_per_statement' => true,
        'simple_to_complex_string_variable' => true,
        'simplified_if_return' => false, // broken
        'space_after_semicolon' => true,
        'standardize_not_equals' => true,
        // 'static_lambda' => true, // TODO
        'switch_case_semicolon_to_colon' => true,
        'switch_case_space' => true,
        'switch_continue_to_break' => true,
        'ternary_operator_spaces' => true,
        'ternary_to_null_coalescing' => false, // Might convert `isset($obj->prop) ? $obj->prop : null`, which PHPStan will complain about if the type of `$obj` doesn't have a `prop` property
        'trailing_comma_in_multiline' => [
            'elements' => ['arrays', 'arguments', 'parameters'],
        ],
        'trim_array_spaces' => true,
        'unary_operator_spaces' => true,
        // 'use_arrow_functions' => true, // TODO
        'visibility_required' => [
            'elements' => ['property', 'method', 'const'],
        ],
        // 'void_return' => true, // TODO
        'whitespace_after_comma_in_array' => false, // freezes with 100% cpu
        'yoda_style' => false, // Maybe later
    ])
    ->setRiskyAllowed(true)
    ->setFinder($finder)
;
