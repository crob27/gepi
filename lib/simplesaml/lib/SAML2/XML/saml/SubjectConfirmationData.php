<?php

/**
 * Class representing SAML 2 SubjectConfirmationData element.
 *
 * @package simpleSAMLphp
 */
class SAML2_XML_saml_SubjectConfirmationData {

	/**
	 * The time before this element is valid, as an unix timestamp.
	 *
	 * @var int|NULL
	 */
	public $NotBefore;


	/**
	 * The time after which this element is invalid, as an unix timestamp.
	 *
	 * @var int|NULL
	 */
	public $NotOnOrAfter;


	/**
	 * The Recipient this Subject is valid for. Either an entity or a location.
	 *
	 * @var string|NULL
	 */
	public $Recipient;


	/**
	 * The ID of the AuthnRequest this is a response to.
	 *
	 * @var string|NULL
	 */
	public $InResponseTo;


	/**
	 * The IP(v6) address of the user.
	 *
	 * @var string|NULL
	 */
	public $Address;


	/**
	 * Initialize (and parse? a SubjectConfirmationData element.
	 *
	 * @param DOMElement|NULL $xml  The XML element we should load.
	 */
	public function __construct(DOMElement $xml = NULL) {

		if ($xml === NULL) {
			return;
		}

		if ($xml->hasAttribute('NotBefore')) {
			$this->NotBefore = SimpleSAML_Utilities::parseSAML2Time($xml->getAttribute('NotBefore'));
		}
		if ($xml->hasAttribute('NotOnOrAfter')) {
			$this->NotOnOrAfter = SimpleSAML_Utilities::parseSAML2Time($xml->getAttribute('NotOnOrAfter'));
		}
		if ($xml->hasAttribute('Recipient')) {
			$this->Recipient = $xml->getAttribute('Recipient');
		}
		if ($xml->hasAttribute('InResponseTo')) {
			$this->InResponseTo = $xml->getAttribute('InResponseTo');
		}
		if ($xml->hasAttribute('Address')) {
			$this->Address = $xml->getAttribute('Address');
		}
	}


	/**
	 * Convert this element to XML.
	 *
	 * @param DOMElement $parent  The parent element we should append this element to.
	 * @return DOMElement  This element, as XML.
	 */
	public function toXML(DOMElement $parent) {
		assert('is_null($this->NotBefore) || is_int($this->NotBefore)');
		assert('is_null($this->NotOnOrAfter) || is_int($this->NotOnOrAfter)');
		assert('is_null($this->Recipient) || is_string($this->Recipient)');
		assert('is_null($this->InResponseTo) || is_string($this->InResponseTo)');
		assert('is_null($this->Address) || is_string($this->Address)');

		$e = $parent->ownerDocument->createElementNS(SAML2_Const::NS_SAML, 'saml:SubjectConfirmationData');
		$parent->appendChild($e);

		if (isset($this->NotBefore)) {
			$e->setAttribute('NotBefore', gmdate('Y-m-d\TH:i:s\Z', $this->NotBefore));
		}
		if (isset($this->NotOnOrAfter)) {
			$e->setAttribute('NotOnOrAfter', gmdate('Y-m-d\TH:i:s\Z', $this->NotOnOrAfter));
		}
		if (isset($this->Recipient)) {
			$e->setAttribute('Recipient', $this->Recipient);
		}
		if (isset($this->InResponseTo)) {
			$e->setAttribute('InResponseTo', $this->InResponseTo);
		}
		if (isset($this->Address)) {
			$e->setAttribute('Address', $this->Address);
		}

		return $e;
	}

}
