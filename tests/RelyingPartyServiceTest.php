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
use Lcobucci\JWT\Signer\Key;
use Lcobucci\JWT\Signer\Rsa\Sha256;
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

    /*public function testTaoKey()
    {
        echo $this->taoFakeToken();
    }*/

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

    public function testSignedTokens()
    {
        $consumeService = $this->prophesize(ConsumerService::class);
        $consumeService->getConfiguration(Argument::type('string'))
            ->shouldBeCalledTimes(1)
            ->willReturn([
                ConsumerService::PROPERTY_ISS => 'http://example.com',
                ConsumerService::PROPERTY_KEY => 'idsrv3test',
                ConsumerService::PROPERTY_SECRET => 'Bag Attributes
    Microsoft Local Key set: <No Values>
    localKeyID: 01 00 00 00 
    friendlyName: {58E2A89C-4A4B-4372-90AC-D6B34A129ACE}
    Microsoft CSP Name: Microsoft Strong Cryptographic Provider
Key Attributes
    X509v3 Key Usage: 10 
-----BEGIN ENCRYPTED PRIVATE KEY-----
MIIFDjBABgkqhkiG9w0BBQ0wMzAbBgkqhkiG9w0BBQwwDgQI+RxvitdKd5wCAggA
MBQGCCqGSIb3DQMHBAizoWc52dWkWQSCBMiK3f95he4li3G/aI63cM2EBclE5/kd
o/2aiAnpQq0StEB+EIzrw2zY6WVcv+94W16Mqc0nUfdbSQd1nV41vWmJVE3GCzKG
4lXtEAjXJde4npTFGYt6YzOtZAW0WHw6w36KnmCCir8seE6hswihKgKqYDCB7Es8
9OTAnDwCptA+CPlYN+xQLD5/gdoIBY1lV/ylDGF/Zpv3LcShd7f4MHdJ6TfdePY/
igFNa2XWTCVvF0lUy11S+lGRfa98BZyJ7QMZfqXe9HqPVNEFdsXwKWSVOqcGcR9V
PocpbfRL2Dn1t5IWRN6RdT4oK8jeiBzXjj9ejHl4+6elIAvuB5CuvVpHrkwx9Kvi
STduKyCwujCDC4/DLYGEMp1eX2PfDfAik5YY6qB82FbalsepA8/gHae7saGQbbbg
654cdezNdUATHoOELj5vWHTQBeS6FfqUrU1PyY3vga7kUcBFCupxXw+7WKVbCq8r
/Mt8jpIsA3V0VABYWFphtEEQI1J3YcTp/MIdLeZXazknaDT2u++nPDRsvLVpO0n+
6x5d8UMCSVEExv2J0fBkaJ7Vbgp28XPpTOb3ylDrFgDzOcGMQjcubo2mXsX+cRtZ
nZ5A96zvogygv6TTfZTcSbFJq1LxqdrA81UNCfGvhDwDKGbY1c483li9K9UIHDrn
6yXH0lBYBiR+XJPETDhyiL/J2NQLXLpXUgzUe31P3Z6RustaKdNF8hRyvreu0QDz
2uFrm17x8RwAAfIJZjTtZSZsBmPjePWgE6e6EXjHWPVITzHv2yrbxe7q1f6XmpKA
grLfnxr1Rjb7VG2h50nGRGBsYvQv4u5xA6jOrjlrDM97dwKmdQfLqq6DDzUuHtdn
BGiWVzRvfJmMQb0ezaAHr3f8RNWt7y6OZB82J1Qa4T16FBePTjDwQXFBW8JaS5sg
riplWWvokjoMWQPC2IWkE7SJ3fQEEB98X7uaUXVlbWDuzoYdBKTuyfLkIBlNVXMP
7C9OENIrfgW9NcRRVUuFQHHun7jnmDevipg7wy5HQqvoBufgIYXToprN1P6/l3da
zWuNan/c8l5TfPdmCiWnYqTVhQUUVErl3BBt0+mTpeuR9YJVUaglx842usJVvSFo
J9om6dWTKDbrl5QAzNLC/YQlEDTzSbrcr579f4pn1nNT2+EyBvaEpGeQe6lrRicB
Np9P6brjcrB1G6BKDSSG18B5in6Nea+taEvBnyHG2Dik7GAaPus9lO4/+AnolcrJ
X8lep5GILdj8foJKx7UTF0ZoNPH7K0YpSZNs2wkfwBzgxqnXM+emEc/rzTzul3ur
9q+Gr6MeCbLZESWpnV2M67+eRqHaV9J2FvRlHe2TpgrlC8DwDz6G78oWSgF8+iq3
I6UE+9weKrdoymQsuzDcVaFnKdoCNzUVJ0IFpEsCr/HJy8G9KVIMcBOrJaJcB8Ba
FNWBmb/B4JWfkxthdh/sMGKLdR1xHbkJzMSVHQw2sNE0cQZhNlANS0U2Ap9uTAtq
WQk3o+KAMLS8/Jws79GKTeURwVcort1I0pvxLZba+ORf6fblc8uWI0/1RpT84zU8
xlbs7dHvTzBRrIcXTAzu7c0Y2zNOK2IpwXGT5qW6LpktqB120EfCZvjgaUqB1673
F7U=
-----END ENCRYPTED PRIVATE KEY-----
Bag Attributes
    localKeyID: 01 00 00 00 
subject=/CN=idsrv3test
issuer=/CN=DevRoot
-----BEGIN CERTIFICATE-----
MIIDBTCCAfGgAwIBAgIQNQb+T2ncIrNA6cKvUA1GWTAJBgUrDgMCHQUAMBIxEDAO
BgNVBAMTB0RldlJvb3QwHhcNMTAwMTIwMjIwMDAwWhcNMjAwMTIwMjIwMDAwWjAV
MRMwEQYDVQQDEwppZHNydjN0ZXN0MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIB
CgKCAQEAqnTksBdxOiOlsmRNd+mMS2M3o1IDpK4uAr0T4/YqO3zYHAGAWTwsq4ms
+NWynqY5HaB4EThNxuq2GWC5JKpO1YirOrwS97B5x9LJyHXPsdJcSikEI9BxOkl6
WLQ0UzPxHdYTLpR4/O+0ILAlXw8NU4+jB4AP8Sn9YGYJ5w0fLw5YmWioXeWvocz1
wHrZdJPxS8XnqHXwMUozVzQj+x6daOv5FmrHU1r9/bbp0a1GLv4BbTtSh4kMyz1h
Xylho0EvPg5p9YIKStbNAW9eNWvv5R8HN7PPei21AsUqxekK0oW9jnEdHewckToX
7x5zULWKwwZIksll0XnVczVgy7fCFwIDAQABo1wwWjATBgNVHSUEDDAKBggrBgEF
BQcDATBDBgNVHQEEPDA6gBDSFgDaV+Q2d2191r6A38tBoRQwEjEQMA4GA1UEAxMH
RGV2Um9vdIIQLFk7exPNg41NRNaeNu0I9jAJBgUrDgMCHQUAA4IBAQBUnMSZxY5x
osMEW6Mz4WEAjNoNv2QvqNmk23RMZGMgr516ROeWS5D3RlTNyU8FkstNCC4maDM3
E0Bi4bbzW3AwrpbluqtcyMN3Pivqdxx+zKWKiORJqqLIvN8CT1fVPxxXb/e9GOda
R8eXSmB0PgNUhM4IjgNkwBbvWC9F/lzvwjlQgciR7d4GfXPYsE1vf8tmdQaY8/Pt
dAkExmbrb9MihdggSoGXlELrPA91Yce+fiRcKY3rQlNWVd4DOoJ/cPXsXwry8pWj
NCo5JD8Q+RQ5yZEy7YPoifwemLhTdsBz3hlZr28oCGJ3kbnpW0xGvQb3VHSTVVbe
ei0CfXoW6iz1
-----END CERTIFICATE-----
',
                ConsumerService::PROPERTY_ENCRYPTION => 'RSA',
            ]);

        $this->service = new RelyingPartyService([
            'consumerService' => $consumeService->reveal()
        ]);

        $signer = new Sha256();

        $token = (new Builder())
            ->issuedBy('http://example.com')
            ->relatedTo('15782409') // (sub claim)
            ->canOnlyBeUsedBy('http://example.org')// Configures the audience (aud claim)
            ->identifiedBy('4f1g23a12aa', true)// Configures the id (jti claim), replicating as a header item
            ->sign($signer, new Key('file://'.$this->getSampleDir() .'root.pem', 'idsrv3test'))
            ->getToken(); // Retrieves the generated token

        $validator = $this->service->validator($token);
        $this->assertTrue($this->service->validate($token, $validator));
    }

    protected function getSampleDir(){
        return __DIR__.DIRECTORY_SEPARATOR.'samples'.DIRECTORY_SEPARATOR;
    }

}
