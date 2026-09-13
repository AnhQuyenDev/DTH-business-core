<?php
namespace Dth\Crm\Tests\Unit; use PHPUnit\Framework\TestCase; use Dth\Crm\Support\Normalizer;
class NormalizerTest extends TestCase {public function test_normalizes_email_phone_tax_and_company():void{$n=new Normalizer;self::assertSame('a@b.com',$n->email(' A@B.COM '));self::assertSame('0971192355',$n->phone('+84 971 192 355'));self::assertSame('0123456789',$n->taxCode(' 0123456789 '));self::assertSame('cong ty abc',$n->companyName('Công ty ABC'));}}
