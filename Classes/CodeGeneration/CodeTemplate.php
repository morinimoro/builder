<?php
namespace FluidTYPO3\Builder\CodeGeneration;

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

class CodeTemplate {

	/**
	 * @var string
	 */
	protected $identifier = NULL;

	/**
	 * @var array
	 */
	protected $variables = array();

	/**
	 * @param string $identifier
	 * @return void
	 */
	public function setIdentifier($identifier) {
		$this->identifier = $identifier;
	}

	/**
	 * @return string
	 */
	public function getIdentifier() {
		return $this->identifier;
	}

	/**
	 * @param array $variables
	 * @return void
	 */
	public function setVariables($variables) {
		$this->variables = $variables;
	}

	/**
	 * @return array
	 */
	public function getVariables() {
		return $this->variables;
	}

	/**
	 * @return string
	 */
	public function render() {
		$identifier = $this->getIdentifier();
		$variables = $this->getVariables();
		if (NULL === $identifier) {
			return NULL;
		}
		$filePathAndFilename = ExtensionManagementUtility::extPath('builder', 'Resources/Private/CodeTemplates/' . $identifier . '.phpt');
		$content = file_get_contents($filePathAndFilename);
		foreach ($variables as $name => $value) {
			$content = str_replace('###' . $name . '###', $value, $content);
		}
		return $content;
	}

}
