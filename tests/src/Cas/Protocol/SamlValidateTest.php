<?php

declare(strict_types=1);

namespace SimpleSAML\Casserver;

use PHPUnit\Framework\TestCase;
use SimpleSAML\Module\casserver\Cas\Protocol\SamlValidateResponder;
use SimpleSAML\SOAP11\XML\env\Envelope;
use SimpleXMLElement;

class SamlValidateTest extends TestCase
{
    /**
     */
    public function testSamlValidatXmlGeneration(): void
    {
        $serviceUrl = 'http://jellyfish.greatvalleyu.com:7777/ssomanager/c/SSB';
        $udcValue = '2F10C881AC7D55942329E149405DC2F5';
        $ticket = [
            'userName' => 'saisusr',
            'attributes' => [
                'UDC_IDENTIFIER' => [$udcValue],
            ],
            'service' => $serviceUrl,
        ];

        $samlValidate = new SamlValidateResponder();
        $xmlString = $samlValidate->convertToSaml($ticket);
        $response = new SimpleXMLElement(strval($xmlString));

        /** @psalm-suppress PossiblyNullPropertyFetch */
        $this->assertEquals($serviceUrl, $response->attributes()->Recipient);
        $this->assertEquals('samlp:Success', $response->Status->StatusCode->attributes()->Value);
        $this->assertEquals('localhost', $response->Assertion->attributes()->Issuer);
        $this->assertEquals($serviceUrl, $response->Assertion->Conditions->AudienceRestrictionCondition->Audience);
        $attributeStatement = $response->Assertion->AttributeStatement;
        $this->assertEquals('saisusr', $attributeStatement->Subject->NameIdentifier);
        $this->assertEquals(
            'urn:oasis:names:tc:SAML:1.0:cm:artifact',
            $attributeStatement->Subject->SubjectConfirmation->ConfirmationMethod,
        );

        $attribute = $attributeStatement->Attribute;
        $this->assertEquals('UDC_IDENTIFIER', $attribute->attributes()->AttributeName);
        $this->assertEquals('http://www.ja-sig.org/products/cas/', $attribute->attributes()->AttributeNamespace);
        $this->assertEquals($udcValue, $attribute->AttributeValue);

        $asSoap = $samlValidate->wrapInSoap($xmlString);

        $this->assertInstanceOf(Envelope::class, $asSoap);
        $this->assertNull($asSoap->getHeader());
        $this->assertNotEmpty($asSoap->getBody());
    }
}
