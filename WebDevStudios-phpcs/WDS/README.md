# WebDevStudios Custom Coding Standards Sniffs
Custom sniffs to maintain a standard for WDS projects.

## Commenting Sniffs
-------------
* **File Comment Sniff**
    * Maintain standards by checking that the required docblock tags are present.
    * Including the sniff: `<rule ref="WDS.Commenting.FileComment" />`
* **Class Comment Sniff**
    * Maintain standards by checking that the required docblock tags are present.
    * Including the sniff: `<rule ref="WDS.Commenting.ClassComment" />`
* **Function Comment Sniff**
    * Maintain standards by checking that the required docblock tags are present.
    * Maintain standards by checking if the @return is present and correctly formated.
    * Including the sniff: `<rule ref="WDS.Commenting.FunctionComment" />`

To include or change the required tags, add or remove the tage in the `FileCommentSniff.php` in the `$tags` property.

```
public $tags = array(
	'@tag' => true,
);
```