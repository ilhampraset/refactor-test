<?php
require 'vendor/autoload.php';

require_once 'TeHelper.php';

use App\Helpers\TeHelper;
use Carbon\Carbon;
use PHPUnit\Framework\TestCase;

class TeHelperTest extends TestCase
{
    /**
     * @dataProvider expirationTimeProvider
     */
    public function testWillExpireAt($due_time, $created_at, $expectedResult, $message)
    {
        $result = TeHelper::willExpireAt($due_time, $created_at);
        $this->assertEquals($expectedResult, $result, $message);
    }

    public function expirationTimeProvider(): array
    {
        return [
            ['2022-01-01 18:00:00', '2022-01-01 10:00:00', '2022-01-01 18:00:00', "time diff <= 90"],
            ['2022-01-01 14:00:00', '2022-01-01 12:00:00', '2022-01-01 13:30:00', "time diff <= 24"],
            ['2022-01-01 14:00:00', '2022-01-01 10:00:00', '2022-01-01 02:00:00', "time diff <= 72"],
            ['2022-01-01 10:00:00', '2022-01-01 08:00:00', '2021-12-31 14:00:00', "time diff > 24"],
        ];
    }
}
