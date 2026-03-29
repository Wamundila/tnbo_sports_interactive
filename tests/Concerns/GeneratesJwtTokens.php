<?php

namespace Tests\Concerns;

trait GeneratesJwtTokens
{
    private const TEST_PRIVATE_KEY = <<<'PEM'
-----BEGIN RSA PRIVATE KEY-----
MIIEowIBAAKCAQEA2jaz8goQc2bVEOPSbzx9U8Fleuoq34Ui7pk8xlnFK1o+LVX3
ctBwNkHSS75FUf2uvPGSaxP69NwSc7kmI514cjQDcVpB4PgnBG/suA4hrjxPf6lR
HNHC/KoyQlpN1hqTGp6LZN37XY5jlc7+WF0HGftUJ9q8rdSpo34k0e7Gt27LpQMX
QmGiSED3x4HxJ23oQ20b4yC10V4yuUidXT+rjZOAabeI+dWpL1U/aFNJDYeFsWR+
N2pbLpTOTccUldom+LnhOh4C3FpjeQjB9ZgT6I+iGbh+WFy60zPdR32wtf6dNECQ
Vrlkebg+jdj3MS4hFGjCRl4K5efW232sjZuD7wIDAQABAoIBABglfXwHCRL8Cg+4
ZgzREL035VbYmq3pOZhVdJguTtchQUga/yrBTelzFyYyg9Ey1ScrRBx9NYPq5k/P
6Rx/zFg20Tq11hxi2U5wZC1pwuhY3CMwRT1/KKh03OLiw0Ix5p1Hdf1PYwVTQEnP
ge2dPa/uU72lQsOpiKwCxWhFl8+sSqKw/gDaWbp1Rv1Vgq+uXnovlCUBMTJSWjMK
GWojLBxtGX/yFIxPb7BlVITd2/snQY2wF/0kkzCuN/fpTjE1LFRDyFszIpwJwXhB
dyuEr4UttnZsmzuj3qt/5f/DZm+pQqtp7zVXk5EtAv+MIuM4wQSYOutPXM1n6peS
+m1uaCECgYEA8Ww8CbbP3xtxsQ+V7F7oo3cP9hOv0c/xjIMB3uqWtdwFe7lK+Akb
8GPI99S6NzhE2fPfq/P6xR+nQARfekvafTYoGkrX5qFENzmtHNtaYKE2hNMqKJGo
dgdCygHhZj5ZC2AWPByn40YzOV3+KdwYaY+QOQNPzQHKpiL4Zq4/InMCgYEA52O3
WtvmrNYlf/RWxFpYbm7026iQKO30U485LCdg3fxZbEt6q0LDv0vdcE53StfUOcGy
Xtt9sdSz7Bx1FXhV4jC0ET45VpObjk/qj+MtWtH3Ie4yNmupCD45mbVkuH9ISzQp
Y6S4F2oA2UDUXKzs5qIH2yzyj+cNzKBRzfoo7ZUCgYEAtdpHpzGTh4WOsEcDMZeU
OX24Ai52I981HhiY0id2+uoPH1FFzWxfJUak3TnaQzoZcuumskoHvXDIdQpWOTLm
E6c1sghqdQlI7yh8492/SEZnYMoHWaPOd4mkn7Gm7XNNc6ofVYxoUmRQtYe9qh5m
LS28/5UlCVGuKlLxNbdPS00CgYB9NGHUkkThpQaplAcXPGO5beSkrzNCUm/wfwFK
uQwbUh75EGaSIRBWhLCPwoWeQ+ccUYk49r+u6A9rZYKdWX3vZLcq1WalSD3V5bxg
m2bBS/fTrlYRSHQwd6snVxXnF0iBGPqEZm8OjFdlN0Ux2Ihfy7FAkbO21imLXfyl
3gUjeQKBgDyrPvxDasiPsusA0PgxqwIsShmGbMfO/k+Ma2hZCZLvplxr2INs2lUZ
oRSBzSN2Wr/BmbSsQiBAZBiQysOCdGzj5vHRYY43aLajDmsZa3EV/6aCXUSMojMW
zXDtGyd51MN9VV57wbAEvTjd/QLMY872B0t46RZ5U4O4jWk6T70S
-----END RSA PRIVATE KEY-----
PEM;

    private const TEST_PUBLIC_KEY = <<<'PEM'
-----BEGIN PUBLIC KEY-----
MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEA2jaz8goQc2bVEOPSbzx9
U8Fleuoq34Ui7pk8xlnFK1o+LVX3ctBwNkHSS75FUf2uvPGSaxP69NwSc7kmI514
cjQDcVpB4PgnBG/suA4hrjxPf6lRHNHC/KoyQlpN1hqTGp6LZN37XY5jlc7+WF0H
GftUJ9q8rdSpo34k0e7Gt27LpQMXQmGiSED3x4HxJ23oQ20b4yC10V4yuUidXT+r
jZOAabeI+dWpL1U/aFNJDYeFsWR+N2pbLpTOTccUldom+LnhOh4C3FpjeQjB9ZgT
6I+iGbh+WFy60zPdR32wtf6dNECQVrlkebg+jdj3MS4hFGjCRl4K5efW232sjZuD
7wIDAQAB
-----END PUBLIC KEY-----
PEM;

    protected function configureJwtTestEnvironment(): void
    {
        config()->set('jwt.public_key', self::TEST_PUBLIC_KEY);
        config()->set('jwt.issuer', null);
        config()->set('jwt.audience', null);
        config()->set('services.interactive.service_key', 'test-service-key');
        config()->set('services.authbox.base_url', 'https://authbox.test');
        config()->set('services.authbox.api_key', 'test-authbox-api-key');
        config()->set('services.authbox.current_user_path', '/api/v1/me');
        config()->set('services.authbox.profile_cache_ttl_seconds', 0);
    }

    protected function authHeaders(array $claims = [], string $userId = 'ts_123'): array
    {
        $defaultClaims = [
            'sub' => $userId,
            'iat' => time(),
            'nbf' => time(),
            'exp' => time() + 3600,
        ];

        return [
            'Authorization' => 'Bearer '.$this->makeJwt(array_merge($defaultClaims, $claims)),
            'X-TNBO-Service-Key' => 'test-service-key',
            'Accept' => 'application/json',
        ];
    }

    protected function makeJwt(array $claims): string
    {
        $header = ['alg' => 'RS256', 'typ' => 'JWT'];
        $segments = [
            $this->base64UrlEncode(json_encode($header, JSON_THROW_ON_ERROR)),
            $this->base64UrlEncode(json_encode($claims, JSON_THROW_ON_ERROR)),
        ];

        $signature = '';
        openssl_sign(implode('.', $segments), $signature, self::TEST_PRIVATE_KEY, OPENSSL_ALGO_SHA256);
        $segments[] = $this->base64UrlEncode($signature);

        return implode('.', $segments);
    }

    private function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }
}
