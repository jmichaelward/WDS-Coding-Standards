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
	 *                                        in the stack passed in $tokens.
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

		$this->processReturn( $phpcs_file, $stack_ptr, $comment_start );

		// Check each tag.
		$this->processTags( $phpcs_file, $stack_ptr, $tokens[ $comment_end ]['comment_opener'] );
	}

	/**
	 * Process the return comment of this function comment.
	 *
	 * @author  Jason Witt
	 * @since  1.1.0
	 *
	 * @param PHP_CodeSniffer_File $phpcs_file    The file being scanned.
	 * @param int                  $stack_ptr     The position of the current token
	 *                                            in the stack passed in $tokens.
	 * @param int                  $comment_start The position in the stack where the comment started.
	 *
	 * @return void
	 */
	protected function processReturn( PHP_CodeSniffer_File $phpcs_file, $stack_ptr, $comment_start ) {
		$tokens = $phpcs_file->getTokens();

		// Skip constructor and destructor.
		$method_name       = $phpcs_file->getDeclarationName( $stack_ptr );
		$is_special_method = ( '__construct' === $method_name || '__destruct' === $method_name );

		$return = null;

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

		if ( null !== $return ) {
			$content = $tokens[ ( $return + 2 ) ]['content'];

			if ( true === empty( $content ) || T_DOC_COMMENT_STRING !== $tokens[ ( $return + 2 ) ]['code'] ) {
				$error = 'Return type missing for @return tag in function comment';
				$phpcs_file->addError( $error, $return, 'MissingReturnType' );
			}
		} else {
			$error = 'Missing @return tag in function comment';
			$phpcs_file->addError( $error, $tokens[ $comment_start ]['comment_closer'], 'MissingReturn' );
		} // End if().
	}
}
