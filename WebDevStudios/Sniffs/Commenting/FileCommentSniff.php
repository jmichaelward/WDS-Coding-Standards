<?php // @codingStandardsIgnoreLine: Class filename is ok.
/**
 * File, function, and class rules (shared):
 *
 * An @since tag is required on all non-structural and structural docblocks.
 *
 * @since  1.1.0
 * @category Commands
 * @package  PHP_CodeSniffer
 */

/**
 * Parses and verifies the doc comments for files.
 *
 * @since    1.1.0
 * @category Commands
 * @package  PHP_CodeSniffer
 */
class WebDevStudios_Sniffs_Commenting_FileCommentSniff implements PHP_CodeSniffer_Sniff {

	/**
	 * Tags in correct order and related info.
	 *
	 * By default, these are used, but may change depending on the comment type.
	 *
	 * @since  1.1
	 *
	 * @var array(string => bool)
	 */
	public $tags = array(
		'@since'  => true,
	);

	/**
	 * Returns an array of tokens this test wants to listen for.
	 *
	 * @since  1.1.0
	 * @author Jason Witt
	 *
	 * @return array
	 */
	public function register() {
		return array(
			T_OPEN_TAG,
		);
	}

	/**
	 * Processes this test, when one of its tokens is encountered.
	 *
	 * @since   1.1.0
	 * @author  Jason Witt
	 *
	 * @param PHP_CodeSniffer_File $phpcs_file The file being scanned.
	 * @param int                  $stack_ptr  The position of the current token
	 *                                         in the stack passed in $tokens.
	 *
	 * @return int
	 */
	public function process( PHP_CodeSniffer_File $phpcs_file, $stack_ptr ) {

		// Find the next non whitespace token.
		$comment_start = $phpcs_file->findNext( T_WHITESPACE, ( $stack_ptr + 1 ), null, true );
		$tokens        = $phpcs_file->getTokens();

		// Allow declare() statements at the top of the file.
		if ( T_DECLARE === $tokens[ $comment_start ]['code'] ) {
			$semicolon     = $phpcs_file->findNext( T_SEMICOLON, ( $comment_start + 1 ) );
			$comment_start = $phpcs_file->findNext( T_WHITESPACE, ( $semicolon + 1 ), null, true );
		}

		// Ignore vim header.
		if ( T_COMMENT === $tokens[ $comment_start ]['code'] ) {
			if ( strstr( $tokens[ $comment_start ]['content'], 'vim:' ) !== false ) {
				$comment_start = $phpcs_file->findNext( T_WHITESPACE, ($comment_start + 1), null, true );
			}
		}

		$error_token = ( $stack_ptr + 1 );
		if ( false === isset( $tokens[ $error_token ] ) ) {
			$error_token--;
		}

		$phpcs_file->num_tokens = 0;

		if ( T_CLOSE_TAG === $tokens[ $comment_start ]['code'] ) {

			// We are only interested if this is the first open tag.
			return ( $phpcs_file->num_tokens + 1 );

		} elseif ( T_COMMENT === $tokens[ $comment_start ]['code'] ) {

			$error = 'You must use "/**" style comments for a file comment';
			$phpcs_file->addError( $error, $error_token, 'WrongStyle' );
			$phpcs_file->recordMetric( $stack_ptr, 'File has doc comment', 'yes' );
			return ( $phpcs_file->num_tokens + 1 );

		} elseif ( false === $comment_start || T_DOC_COMMENT_OPEN_TAG !== $tokens[ $comment_start ]['code'] ) {

			$phpcs_file->addError( 'Missing file doc comment', $error_token, 'Missing' );
			$phpcs_file->recordMetric( $stack_ptr, 'File has doc comment', 'no' );
			return ( $phpcs_file->num_tokens + 1 );
		}

		$comment_end = $tokens[ $comment_start ]['comment_closer'];
		$next_token  = $phpcs_file->findNext( T_WHITESPACE, ( $comment_end + 1 ), null, true );

		$ignore = array(
			T_CLASS,
			T_INTERFACE,
			T_TRAIT,
			T_FUNCTION,
			T_CLOSURE,
			T_PUBLIC,
			T_PRIVATE,
			T_PROTECTED,
			T_FINAL,
			T_STATIC,
			T_ABSTRACT,
			T_CONST,
			T_PROPERTY,
		);

		if ( true === in_array( $tokens[ $next_token ]['code'], $ignore, true ) ) {
			$phpcs_file->addError( 'Missing file doc comment', $stack_ptr, 'Missing' );
			$phpcs_file->recordMetric( $stack_ptr, 'File has doc comment', 'no' );
			return ( $phpcs_file->num_tokens + 1 );
		}

		$phpcs_file->recordMetric( $stack_ptr, 'File has doc comment', 'yes' );

		// Check the PHP Version, which should be in some text before the first tag.
		$found = false;
		for ( $i = ($comment_start + 1); $i < $comment_end; $i++ ) {
			if ( T_DOC_COMMENT_TAG === $tokens[ $i ]['code'] ) {
				break;
			} elseif ( T_DOC_COMMENT_STRING === $tokens[ $i ]['code'] && strstr( strtolower( $tokens[ $i ]['content'] ), 'php version' ) !== false ) {
				$found = true;
				break;
			}
		}

		// Check each tag.
		$this->processTags( $phpcs_file, $stack_ptr, $comment_start );

		// Ignore the rest of the file.
		return ( $phpcs_file->num_tokens + 1 );
	}

	/**
	 * Processes each required or optional tag.
	 *
	 * @author  Jason Witt, Aubrey Portwood
	 * @since   1.1.0
	 *
	 * @param PHP_CodeSniffer_File $phpcs_file    The file being scanned.
	 * @param int                  $stack_ptr     The position of the current token
	 *                                            in the stack passed in $tokens.
	 * @param int                  $comment_start Position in the stack where the comment started.
	 */
	protected function processTags( PHP_CodeSniffer_File $phpcs_file, $stack_ptr, $comment_start ) {
		$tokens = $phpcs_file->getTokens();
		$class = get_class( $this );

		// Set the default doc_block.
		$doc_block = str_ireplace( 'WebDevStudios_Sniffs_Commenting_', '', $class ); // Clear out the base.
		$doc_block = strtolower( str_ireplace( 'Sniff', '', $doc_block ) ); // Clear out the sniff.
		$doc_block = strtolower( str_ireplace( 'comment', '', $doc_block ) ); // clear out the comment if any.

		if ( 'file' === $doc_block ) {

			// This is a file comment, process this with different tags.
			$this->tags = array(
				'@since'   => true, // @since required on file comment.
				'@package' => true, // @package required as well.
			);
		}

		$comment_end = $tokens[ $comment_start ]['comment_closer'];
		$found_tags  = array();
		$tag_tokens  = array();

		foreach ( $tokens[ $comment_start ]['comment_tags'] as $tag ) {
			$name = $tokens[ $tag ]['content'];
			if ( isset( $this->tags[ $name ] ) === false ) {
				continue;
			}
			$found_tags[]          = $name;
			$tag_tokens[ $name ][] = $tag;
			$string = $phpcs_file->findNext( T_DOC_COMMENT_STRING, $tag, $comment_end );
			if ( false === $string || $tokens[ $string ]['line'] !== $tokens[ $tag ]['line'] ) {
				$error = 'Content missing for %s tag in %s comment';
				$data  = array(
					$name,
					$doc_block,
				);
				$phpcs_file->addError( $error, $tag, 'Empty' . ucfirst( substr( $name, 1 ) ) . 'Tag', $data );
				continue;
			}
		} // End foreach().

		// Check if the tags are in the correct position.
		$pos = 0;
		foreach ( $this->tags as $tag => $tag_data ) {
			if ( isset( $tag_tokens[ $tag ] ) === false ) {
				if ( true === $tag_data ) {
					$error = 'Missing %s tag in %s docblock comment';
					$data  = array(
						$tag,
						$doc_block,
						);
					$phpcs_file->addError( $error, $comment_end, 'Missing' . ucfirst( substr( $tag, 1 ) ) . 'Tag', $data );
				}
				continue;
			} else {
				$method = 'process' . substr( $tag, 1 );
				if ( method_exists( $this, $method ) === true ) {

					// Process each tag if a method is defined.
					call_user_func( array( $this, $method ), $phpcs_file, $tag_tokens[ $tag ] );
				}
			}

			if ( isset( $found_tags[ $pos ] ) === false ) {
				break;
			}

			if ( $found_tags[ $pos ] !== $tag ) {
				$error = 'The tag in position %s should be the %s tag';
				$data  = array(
					( $pos + 1 ),
					$tag,
				);
				$phpcs_file->addError( $error, $tokens[ $comment_start ]['comment_tags'][ $pos ], ucfirst( substr( $tag, 1 ) ) . 'TagOrder', $data );
			}

			// Account for multiple tags.
			$pos++;
			while ( isset( $found_tags[ $pos ] ) === true && $found_tags[ $pos ] === $tag ) {
				$pos++;
			}
		} // End foreach().
	}

	/**
	 * Process the author tag(s) that this header comment has.
	 *
	 * @author Jason Witt
	 * @since  1.1.0
	 *
	 * @param PHP_CodeSniffer_File $phpcs_file The file being scanned.
	 * @param array                $tags       The tokens for these tags.
	 */
	protected function processAuthor( PHP_CodeSniffer_File $phpcs_file, array $tags ) {
		$tokens = $phpcs_file->getTokens();

		foreach ( $tags as $tag ) {
			if ( T_DOC_COMMENT_STRING !== $tokens[ ( $tag + 2 ) ]['code'] ) {

				// No content.
				continue;
			}

			$content = $tokens[ ( $tag + 2 ) ]['content'];
			$local   = '\da-zA-Z-_+';

			// Dot character cannot be the first or last character in the local-part.
			$local_middle = $local . '.\w';
		}
	}
}
