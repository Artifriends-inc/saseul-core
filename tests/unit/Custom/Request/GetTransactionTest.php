<?php

use PHPUnit\Framework\TestCase;
use Saseul\Common\AbstractRequest;
use Saseul\Util\DateTime;
use Saseul\Custom\Request\GetTransaction;
use Saseul\System\Key;

class GetTransactionTest extends TestCase
{
    public function testSutInheritsAbstractRequest()
    {
        # Arrange
        $sut = new GetTransaction();

        # Assert
        $this->assertInstanceOf(AbstractRequest::class, $sut);
    }

    public function testGivenNonVersionThenGetValidityMethodReturnsFalse()
    {
        // Arrange
        $request = array(
            "type" => "GetTransaction",
            "from" => "0x6f258c97ad7848aef661465018dc48e55131eff91c4e20",
            "timestamp" => DateTime::Date()
        );
        $thash = hash('sha256', json_encode($request));
        $private_key = "a609aca90f9338da02e640c7df8ae760211bef48031973ee00000169dca49c4d";
        $public_key = "2704e240b3e806f476211694c2bda537bcf56941199f4447468021f9c3833a33";
        $signature = Key::makeSignature($thash, $private_key, $public_key);
        $sut = new GetTransaction();
        $sut->initialize($request, $thash, $public_key, $signature);

        // Act
        $actual = $sut->getValidity();

        // Assert
        $this->assertFalse($actual);
    }

    public function testGivenNonTimestampThenGetValidityMethodReturnsFalse()
    {
        // Arrange
        $request = array(
            "type" => "GetTransaction",
            "from" => "0x6f258c97ad7848aef661465018dc48e55131eff91c4e20",
            "version" => "1.0"
        );
        $thash = hash('sha256', json_encode($request));
        $private_key = "a609aca90f9338da02e640c7df8ae760211bef48031973ee00000169dca49c4d";
        $public_key = "2704e240b3e806f476211694c2bda537bcf56941199f4447468021f9c3833a33";
        $signature = Key::makeSignature($thash, $private_key, $public_key);
        $sut = new GetTransaction();
        $sut->initialize($request, $thash, $public_key, $signature);

        // Act
        $actual = $sut->getValidity();

        // Assert
        $this->assertFalse($actual);
    }

    public function testGivenInvalidFromThenGetValidityMethodReturnsFalse()
    {
        // Arrange
        $request = array(
            "type" => "GetTransaction",
            "from" => "0x000000000000000000000000000000000000000000000",
            "version" => "1.0",
            "timestamp" => DateTime::Date()
        );
        $thash = hash('sha256', json_encode($request));
        $private_key = "a609aca90f9338da02e640c7df8ae760211bef48031973ee00000169dca49c4d";
        $public_key = "2704e240b3e806f476211694c2bda537bcf56941199f4447468021f9c3833a33";
        $signature = Key::makeSignature($thash, $private_key, $public_key);
        $sut = new GetTransaction();
        $sut->initialize($request, $thash, $public_key, $signature);

        // Act
        $actual = $sut->getValidity();

        // Assert
        $this->assertFalse($actual);
    }

    public function testGivenInvalidSignatureThenGetValidityMethodReturnsFalse()
    {
        // Arrange
        $request = array(
            "type" => "GetTransaction",
            "from" => "0x6f258c97ad7848aef661465018dc48e55131eff91c4e20",
            "version" => "1.0",
            "timestamp" => DateTime::Date()
        );
        $thash = hash('sha256', json_encode($request));
        $private_key = "a609aca90f9338da02e640c7df8ae760211bef48031973ee12345169dca49cff";
        $public_key = "2704e240b3e806f476211694c2bda537bcf56941199f4447468021f9c3833a33";
        $signature = Key::makeSignature($thash, $private_key, $public_key);
        $sut = new GetTransaction();
        $sut->initialize($request, $thash, $public_key, $signature);

        // Act
        $actual = $sut->getValidity();

        // Assert
        $this->assertFalse($actual);
    }

    public function testGivenValidRequestThenGetValidityMethodReturnsTrue()
    {
        // Arrange
        $request = array(
            "type" => "GetTransaction",
            "from" => "0x6f258c97ad7848aef661465018dc48e55131eff91c4e20",
            "version" => "1.0",
            "timestamp" => DateTime::Date()
        );
        $thash = hash('sha256', json_encode($request));
        $private_key = "a609aca90f9338da02e640c7df8ae760211bef48031973ee00000169dca49c4d";
        $public_key = "2704e240b3e806f476211694c2bda537bcf56941199f4447468021f9c3833a33";
        $signature = Key::makeSignature($thash, $private_key, $public_key);
        $sut = new GetTransaction();
        $sut->initialize($request, $thash, $public_key, $signature);

        // Act
        $actual = $sut->getValidity();

        // Assert
        $this->assertTrue($actual);
    }
}
