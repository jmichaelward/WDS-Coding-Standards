<?php
/**
 * Parses and verifies the doc comments for classes.
 *
 * @author   WebDevStudios
 * @since    1.0.0
 * @category Commands
 * @package  PHP_CodeSniffer
 */

/**
 * Parses and verifies the doc comments for classes.
 *
 * @author   WebDevStudios
 * @since    1.0.0
 * @category Commands
 * @package  PHP_CodeSniffer
 */
class WDS_Sniffs_Commenting_ClassCommentSniff extends WDS_Sniffs_Commenting_FileCommentSniff {

	/**
	 * Returns an array of tokens this test wants to listen for.
	 *
	 * @return array
	 */
	public function register() {
		return array(
			T_CLASS,
			T_INTERFACE,
		);

	}//end register()

	/**
	 * Processes this test, when one of its tokens is encountered.
	 *
	 * @param PHP_CodeSniffer_File $phpcs_file The file being scanned.
	 * @param int                  $stack_ptr  The position of the current token
	 *                                         in the stack passed in $tokens.
	 *
	 * @return void
	 */
	public function process( PHP_CodeSniffer_File $phpcs_file, $stack_ptr ) {

		$tokens     = $phpcs_file->getTokens();
		$type       = strtolower( $tokens[ $stack_ptr ]['content'] );
		$error_data = array( $type );

		// @codingStandardsIgnoreLine
		$find   = PHP_CodeSniffer_Tokens::$methodPrefixes;
		$find[] = T_WHITESPACE;

		$comment_end = $phpcs_file->findPrevious( $find, ( $stack_ptr - 1 ), null, true );
		if ( T_DOC_COMMENT_CLOSE_TAG !== $tokens[ $comment_end ]['code'] && T_COMMENT !== $tokens[ $comment_end ]['code'] ) {
			$phpcs_file->addError( 'Missing class doc comment', $stack_ptr, 'Missing' );
			$phpcs_file->recordMetric( $stack_ptr, 'Class has doc comment', 'no' );
			return;
		}

		$phpcs_file->recordMetric( $stack_ptr, 'Class has doc comment', 'yes' );

		if ( T_COMMENT === $tokens[ $comment_end ]['code'] ) {
			$phpcs_file->addError( 'You must use "/**" style comments for a class comment', $stack_ptr, 'WrongStyle' );
			return;
		}

		// Check each tag.
		$this->processTags( $phpcs_file, $stack_ptr, $tokens[ $comment_end ]['comment_opener'] );
	}//end process()
}//end class
