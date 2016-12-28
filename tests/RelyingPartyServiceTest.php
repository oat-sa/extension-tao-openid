<?php
/**
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; under version 2
 * of the License (non-upgradable).
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 *
 * Copyright (c) 2016  (original work) Open Assessment Technologies SA;
 *
 * @author Alexander Zagovorichev <zagovorichev@1pt.com>
 */

namespace oat\taoOpenId\tests;


use Lcobucci\JWT\Builder;
use Lcobucci\JWT\Parser;
use Lcobucci\JWT\Token;
use oat\tao\test\TaoPhpUnitTestRunner;
use oat\taoOpenId\model\ConsumerService;
use oat\taoOpenId\model\RelyingPartyService;
use OutOfBoundsException;
use Prophecy\Argument;


class RelyingPartyServiceTest extends TaoPhpUnitTestRunner
{
    /**
     * @var RelyingPartyService
     */
    private $service;

    private function _prepare($shouldBeCalled = [])
    {
        /** @var ConsumerService $consumeService */
        $consumeService = $this->prophesize(ConsumerService::class);
        $consumeService->getConfiguration(Argument::type('string'))
            ->shouldBeCalledTimes($shouldBeCalled['consumeService->getConfiguration'])
            ->willReturn([
                ConsumerService::PROPERTY_ISS => 'http://example.com',
                ConsumerService::PROPERTY_KEY => 'UniqueRpKeyForOP',
                ConsumerService::PROPERTY_SECRET => 'somethingVerySafe',
            ]);

        $this->service = new RelyingPartyService([
            'consumerService' => $consumeService->reveal()
        ]);
    }

    public function tokensProvider()
    {
        $tokens = $this->getJwtTokens();
        $tokens[] = [$this->getFakeToken()];
        return $tokens;
    }

    public function getJwtTokens()
    {
        $tokens = [];
        foreach (["eyJ0eXAiOiJKV1QiLCJhbGciOiJub25lIiwianRpIjoiNGYxZzIzYTEyYWEifQ\n.eyJpc3MiOiJodHRwOlwvXC9leGFtcGxlLmNvbSIsImF1ZCI6Imh0dHA6XC9cL2V4YW1wbGUub3JnIiwianRpIjoiNGYxZzIzYTEyYWEiLCJpYXQiOjE0ODE2MTYxNzksIm5iZiI6MTQ4MTYxNjIzOSwiZXhwIjoxNDgxNjE5Nzc5LCJ1aWQiOjF9."]
                 as $token) {
            $tokens[][] = (new Parser())->parse((string) $token);
        }

        return $tokens;
    }

    /**
     * @return Token
     */
    private function getFakeToken()
    {
        return (new Builder())->issuedBy('http://example.com')// Configures the issuer (iss claim)
        ->canOnlyBeUsedBy('http://example.org')// Configures the audience (aud claim)
        ->relatedTo('15782409')
        ->identifiedBy('4f1g23a12aa', true)// Configures the id (jti claim), replicating as a header item
        ->issuedAt(time())// Configures the time that the token was issue (iat claim)
        ->canOnlyBeUsedAfter(time() + 60)// Configures the time that the token can be used (nbf claim)
        ->expiresAt(time() + 3600)// Configures the expiration time of the token (nbf claim)
        ->with('uid', 1)// Configures a new claim, called "uid"
        ->getToken(); // Retrieves the generated token
    }

    public function taoFakeToken()
    {
        $roles = new \stdClass();
        $a = '8196';
        $b = '7352';
        $roles->$a = [12, 42];
        $roles->$b = [12];

        return (new Builder())->issuedBy('https://registry.nccer.org')// Configures the issuer (iss claim)
        ->canOnlyBeUsedBy('tao')// Configures the audience (aud claim)
        ->relatedTo('15782409')
        ->identifiedBy('4f1g23a12aa', true)// Configures the id (jti claim), replicating as a header item
        ->issuedAt(time())// Configures the time that the token was issue (iat claim)
        ->canOnlyBeUsedAfter(time() + 60)// Configures the time that the token can be used (nbf claim)
        ->expiresAt(time() + 3600)// Configures the expiration time of the token (nbf claim)
        ->with('name', 'frotto baggins')// Configures a new claim, called "name"
        ->with('lang', 'en_US')// Configures a new claim, called "locale"
        ->with('https://nccer.org/roles', $roles)// Configures a new claim
        ->getToken(); // Retrieves the generated token
    }

    public function testTaoKey()
    {
        echo $this->taoFakeToken();
    }

    public function testTimeValidator()
    {
        $this->_prepare(['consumeService->getConfiguration' => 1]);

        $token = $this->getFakeToken();
        $validator = $this->service->validator($token);
        // false, because we created a token that cannot be used before of `time() + 60`
        $this->assertFalse($this->service->validate($token, $validator));
        // changing the validation time to future
        $validator->setCurrentTime(time() + 60);
        // true, because validation information is equals to data contained on the token
        $this->assertTrue($this->service->validate($token, $validator));
        // changing the validation time to the far future
        $validator->setCurrentTime(time() + 4000);
        $this->assertFalse($this->service->validate($token, $validator));
    }

    /**
     * @expectedException OutOfBoundsException
     * @expectedExceptionMessage Requested claim is not configured
     */
    public function testEmptyToken()
    {
        $this->_prepare(['consumeService->getConfiguration' => 1]);
        $token = (new Builder())->getToken();
        $validator = $this->service->validator($token);
        $this->assertFalse($this->service->validate($token, $validator));
    }

    public function testAllRequiredFields()
    {
        $this->_prepare(['consumeService->getConfiguration' => 1]);

        $token = (new Builder())
            ->issuedBy('http://example.com')
            ->relatedTo('15782409') // (sub claim)
            ->canOnlyBeUsedBy('http://example.org')// Configures the audience (aud claim)
            ->identifiedBy('4f1g23a12aa', true)// Configures the id (jti claim), replicating as a header item
            ->getToken(); // Retrieves the generated token

        $validator = $this->service->validator($token);
        $this->assertTrue($this->service->validate($token, $validator));
    }

    public function testUnexpectedIssuer()
    {
        $this->_prepare(['consumeService->getConfiguration' => 1]);

        $token = (new Builder())
            ->issuedBy('http://example.com')
            ->relatedTo('15782409')
            ->canOnlyBeUsedBy('http://example.org')// Configures the audience (aud claim)
            ->identifiedBy('4f1g23a12aa', true)// Configures the id (jti claim), replicating as a header item
            ->getToken(); // Retrieves the generated token

        $validator = $this->service->validator($token);
        $validator->setIssuer('http://oops.wrong');
        $this->assertFalse($this->service->validate($token, $validator));
    }

    public function testUnexpectedAudience()
    {
        $this->_prepare(['consumeService->getConfiguration' => 1]);

        $token = (new Builder())
            ->issuedBy('http://example.com')
            ->relatedTo('15782409')
            ->canOnlyBeUsedBy('http://example.org')// Configures the audience (aud claim)
            ->identifiedBy('4f1g23a12aa', true)// Configures the id (jti claim), replicating as a header item
            ->getToken(); // Retrieves the generated token

        $validator = $this->service->validator($token);
        $validator->setAudience('http://oops.wrong');
        $this->assertFalse($this->service->validate($token, $validator));
    }
}
