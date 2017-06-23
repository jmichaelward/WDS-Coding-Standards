<?php
/**
 * Parses and verifies the doc comments for functions.
 *
 * @since  1.1.0
 * @category Commands
 * @package  PHP_CodeSniffer
 */

/**
 * Parses and verifies the doc comments for functions.
 *
 * @author   WebDevStudios
 * @since    1.0.0
 * @category Commands
 * @package  PHP_CodeSniffer
 */
class WebDevStudios_Sniffs_Commenting_FunctionCommentSniff extends WebDevStudios_Sniffs_Commenting_FileCommentSniff {

	/**
	 * Returns an array of tokens this test wants to listen for.
	 *
	 * @author  Jason Witt
	 * @since  1.1.0
	 *
	 * @return array
	 */
	public function register() {
		return array(
			T_FUNCTION,
		);
	}

	/**
	 * Processes this test, when one of its tokens is encountered.
	 *
	 * @author Jason Witt
	 * @since  1.1.0
	 *
	 * @param PHP_CodeSniffer_File $phpcs_file The file being scanned.
	 * @param int                  $stack_ptr  The position of the current token
	 *                                         in the stack passed in $tokens.
	 *
	 * @return void
	 */
	public function process( PHP_CodeSniffer_File $phpcs_file, $stack_ptr ) {
		$tokens = $phpcs_file->getTokens();

		// @codingStandardsIgnoreLine
		$find   = PHP_CodeSniffer_Tokens::$methodPrefixes;
		$find[] = T_WHITESPACE;

		$comment_end = $phpcs_file->findPrevious( $find, ( $stack_ptr - 1 ), null, true );

		if ( T_COMMENT === $tokens[ $comment_end ]['code'] ) {
			// Inline comments might just be closing comments for
			// control structures or functions instead of function comments
			// using the wrong comment type. If there is other code on the line,
			// assume they relate to that code.
			$prev = $phpcs_file->findPrevious( $find, ($comment_end - 1), null, true );
			if ( false !== $prev && $tokens[ $prev ]['line'] === $tokens[ $comment_end ]['line'] ) {
				$comment_end = $prev;
			}
		}

		if ( T_DOC_COMMENT_CLOSE_TAG !== $tokens[ $comment_end ]['code'] && T_COMMENT !== $tokens[ $comment_end ]['code'] ) {
			$phpcs_file->addError( 'Missing function doc comment', $stack_ptr, 'Missing' );
			$phpcs_file->recordMetric( $stack_ptr, 'Function has doc comment', 'no' );
			return;
		} else {
			$phpcs_file->recordMetric( $stack_ptr, 'Function has doc comment', 'yes' );
		}

		if ( T_COMMENT === $tokens[ $comment_end ]['code'] ) {
			$phpcs_file->addError( 'You must use "/**" style comments for a function comment', $stack_ptr, 'WrongStyle' );
			return;
		}

		if ( ( $tokens[ $stack_ptr ]['line'] - 1 ) !== $tokens[ $comment_end ]['line'] ) {
			$error = 'There must be no blank lines after the function comment';
			$phpcs_file->addError( $error, $comment_end, 'SpacingAfter' );
		}

		$comment_start = $tokens[ $comment_end ]['comment_opener'];
		foreach ( $tokens[ $comment_start ]['comment_tags'] as $tag ) {
			if ( '@see' === $tokens[ $tag ]['content'] ) {
				// Make sure the tag isn't empty.
				$string = $phpcs_file->findNext( T_DOC_COMMENT_STRING, $tag, $comment_end );
				if ( false === $string || $tokens[ $string ]['line'] !== $tokens[ $tag ]['line'] ) {
					$error = 'Content missing for @see tag in function comment';
					$phpcs_file->addError( $error, $tag, 'EmptySees' );
				}
			}
		}

		$this->processReturn( $phpcs_file, $stack_ptr, $comment_start, $comment_end );

		// Check each tag.
		$this->processTags( $phpcs_file, $stack_ptr, $tokens[ $comment_end ]['comment_opener'] );
	}

	/**
	 * Does a section of code have a return statement?
	 *
	 * @author Aubrey Portwood
	 * @since  1.1.0
	 *
	 * @param PHP_CodeSniffer_File $phpcs_file    The file being scanned.
	 * @param int                  $start         The position of the start of the statement.
	 * @param int                  $end           The position of the end of the statement.
	 * @param array                $tokens        The tokens.
	 *
	 * @return  boolean True if there is a return statement.
	 */
	protected function has_return( $phpcs_file, $start, $end, $tokens ) {

		// Find where a return is in between where the method starts and ends.
		$return = $phpcs_file->findNext( array( T_RETURN ), $start, $end );

		// Is this thing a return statement?
		$is_t_return = 'T_RETURN' === $tokens[ $return ]['type'];

		// Is it within the scope of the start and end?
		$before_end = $tokens[ $return ]['line'] < $tokens[ $end ]['line'];

		// If $return is indeed is a return, and it's before the end of the method, we have a return statement.
		if ( $is_t_return && $before_end ) {

			// We found a return in the statement.
			return true;
		}

		// We didn't find a return in the statement.
		return false;
	}

	/**
	 * Process the return comment of this function comment.
	 *
	 * @author  Jason Witt, Aubrey Portwood
	 * @since  1.1.0
	 *
	 * @param PHP_CodeSniffer_File $phpcs_file    The file being scanned.
	 * @param int                  $stack_ptr     The position of the current token
	 *                                            in the stack passed in $tokens.
	 * @param int                  $comment_start The position in the stack where the comment started.
	 * @param int                  $comment_end   The position in the stack where the comment ends.
	 *
	 * @return void
	 */
	protected function processReturn( PHP_CodeSniffer_File $phpcs_file, $stack_ptr, $comment_start, $comment_end ) {
		$tokens = $phpcs_file->getTokens();

		// Skip constructor and destructor.
		$method_name       = $phpcs_file->getDeclarationName( $stack_ptr );
		$is_special_method = ( '__construct' === $method_name || '__destruct' === $method_name );
		$return            = null;

		foreach ( $tokens[ $comment_start ]['comment_tags'] as $tag ) {
			if ( '@return' === $tokens[ $tag ]['content'] ) {
				if ( null !== $return ) {
					$error = 'Only 1 @return tag is allowed in a function comment';
					$phpcs_file->addError( $error, $tag, 'DuplicateReturn' );
					return;
				}

				$return = $tag;
			}
		}

		if ( true === $is_special_method ) {
			return;
		}

		// There is an @return, what is it's content?
		$content = $tokens[ ( $return + 2 ) ]['content'];

		// Does the @return have empty content?
		$empty_content = ( true === empty( $content ) || T_DOC_COMMENT_STRING !== $tokens[ ( $return + 2 ) ]['code'] );

		// Is there an @return?
		$at_return = null !== $return;

		if ( $at_return && $empty_content ) {

			// You have an @return, but it has empty content.
			$error = 'Return type missing for @return tag in function comment';
			$phpcs_file->addError( $error, $return, 'MissingReturnType' );
			return; // Bail here.
		}

		// Where is the end of the statement.
		$method_end  = $phpcs_file->findEndOfStatement( $comment_end + 2 );

		// Does this statement have a return?
		$has_return = $this->has_return( $phpcs_file, $comment_end + 2, $method_end, $tokens );

		if ( ! $at_return && $has_return ) {

			// No @return and there's a return statement in the method/function.
			$error = 'Missing @return tag in function comment';
			$phpcs_file->addError( $error, $tokens[ $comment_start ]['comment_closer'], 'MissingReturn' );
			return;
		}
	}
}
