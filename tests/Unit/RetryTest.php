<?php

namespace AlibabaCloud\Dara\Tests;

use AlibabaCloud\Dara\Dara;
use AlibabaCloud\Dara\Exception\DaraException;
use AlibabaCloud\Dara\Exception\DaraRespException;
use AlibabaCloud\Dara\RetryPolicy\RetryPolicyContext;
use AlibabaCloud\Dara\RetryPolicy\RetryCondition;
use AlibabaCloud\Dara\RetryPolicy\RetryOptions;
use AlibabaCloud\Dara\RetryPolicy\BackoffPolicy;
use AlibabaCloud\Dara\RetryPolicy\EqualJitterBackoffPolicy;
use AlibabaCloud\Dara\RetryPolicy\ExponentialBackoffPolicy;
use AlibabaCloud\Dara\RetryPolicy\FixedBackoffPolicy;
use AlibabaCloud\Dara\RetryPolicy\FullJitterBackoffPolicy;
use AlibabaCloud\Dara\RetryPolicy\RandomBackoffPolic;
use PHPUnit\Framework\TestCase;

class AEx extends DaraException {
    /**
    * @var string[]
    */
    public $data;
  
    public function __construct($map)
    {
      parent::__construct($map);
      $this->name = 'AEx';
    }

    public function getDate(){
        return $this->data;
    }

} 

class BEx extends DaraException {
    /**
    * @var string[]
    */
    public $data;
  
    public function __construct($map)
    {
      parent::__construct($map);
      $this->name = 'BEx';
    }

    public function getDate(){
        return $this->data;
    }
}

class CEx extends DaraRespException {
    /**
    * @var string[]
    */
    public $data;
  
    public function __construct($map)
    {
      parent::__construct($map);
      $this->name = 'BEx';
    }

    public function getDate(){
        return $this->data;
    }
}



/**
 * @internal
 * @coversNothing
 */
class RetryTest extends TestCase
{

    public function testShouldRetryWithNull()
    {
        $context = new RetryPolicyContext([
            'retriesAttempted' => 3,
        ]);
        $this->assertFalse(Dara::shouldRetry(null, $context));
    }

    public function testShouldRetry()
    {
        

        $condition1 = new RetryCondition([
            'maxAttempts' => 3,
            'exception' => ['AEx'],
            'errorCode' => ['A1Ex']
        ]);
        $options = new RetryOptions([
            'retryable' => true,
            'retryCondition' => [$condition1]
        ]);
        $context = new RetryPolicyContext([
            'retriesAttempted' => 0,
        ]);
        $this->assertTrue(Dara::shouldRetry($options, $context));

        $context = new RetryPolicyContext([
            'retriesAttempted' => 3,
            'exception' => new AEx([
                'errCode' => 'A1Ex',
                'message' => 'a1 error'
            ])
        ]);
        $this->assertFalse(Dara::shouldRetry($options, $context));

        $options = new RetryOptions([
            'retryable' => true
        ]);
        $this->assertFalse(Dara::shouldRetry($options, $context));

        $options = new RetryOptions([
            'retryable' => true,
            'retryCondition' => [$condition1]
        ]);
        $context = new RetryPolicyContext([
            'retriesAttempted' => 2,
            'exception' => new AEx([
                'errCode' => 'A1Ex',
                'message' => 'a1 error'
            ])
        ]);
        $this->assertTrue(Dara::shouldRetry($options, $context));

        $context = new RetryPolicyContext([
            'retriesAttempted' => 2,
            'exception' => new AEx([
                'errCode' => 'B1Ex',
                'message' => 'b1 error'
            ])
        ]);
        $this->assertTrue(Dara::shouldRetry($options, $context));

        $context = new RetryPolicyContext([
            'retriesAttempted' => 2,
            'exception' => new BEx([
                'errCode' => 'B1Ex',
                'message' => 'b1 error'
            ])
        ]);
        $this->assertFalse(Dara::shouldRetry($options, $context));

        $context = new RetryPolicyContext([
            'retriesAttempted' => 2,
            'exception' => new BEx([
                'errCode' => 'A1Ex',
                'message' => 'b1 error'
            ])
        ]);
        $this->assertTrue(Dara::shouldRetry($options, $context));

        $condition2 = new RetryCondition([
            'maxAttempts' => 3,
            'exception' => ['BEx'],
            'errorCode' => ['B1Ex']
        ]);
        $options = new RetryOptions([
            'retryable' => true,
            'retryCondition' => [$condition2],
            'noRetryCondition' => [$condition2],
        ]);
        $context = new RetryPolicyContext([
            'retriesAttempted' => 2,
            'exception' => new AEx([
                'errCode' => 'B1Ex',
                'message' => 'b1 error'
            ])
        ]);
        $this->assertFalse(Dara::shouldRetry($options, $context));

        $context = new RetryPolicyContext([
            'retriesAttempted' => 2,
            'exception' => new BEx([
                'errCode' => 'A1Ex',
                'message' => 'b1 error'
            ])
        ]);
        $this->assertFalse(Dara::shouldRetry($options, $context));

        $context = new RetryPolicyContext([
            'retriesAttempted' => 2,
            'exception' => new BEx([
                'errCode' => 'A1Ex',
                'message' => 'b1 error'
            ])
        ]);
        $this->assertFalse(Dara::shouldRetry($options, $context));
    }

    public function testGetBackoffDelay()
    {
        $condition = new RetryCondition([
            'maxAttempts' => 3,
            'exception' => ['AEx'],
            'errorCode' => ['A1Ex']
        ]);
        $options = new RetryOptions([
            'retryable' => true,
            'retryCondition' => [$condition]
        ]);
        $context = new RetryPolicyContext([
            'retriesAttempted' => 2,
            'exception' => new AEx([
                'errCode' => 'A1Ex',
                'message' => 'a1 error'
            ])
        ]);
        $this->assertEquals(Dara::getBackoffDelay($options, $context), 100);

        $context = new RetryPolicyContext([
            'retriesAttempted' => 2,
            'exception' => new BEx([
                'errCode' => 'B1Ex',
                'message' => 'a1 error'
            ])
        ]);
        $this->assertEquals(Dara::getBackoffDelay($options, $context), 100);

        $fixedPolicy = BackoffPolicy::newBackoffPolicy([
            'policy' => 'Fixed',
            'period' => 1000
        ]);
        $condition1 = new RetryCondition([
            'maxAttempts' => 3,
            'exception' => ['AEx'],
            'errorCode' => ['A1Ex'],
            'backoff' => $fixedPolicy,
        ]);
        $options = new RetryOptions([
            'retryable' => true,
            'retryCondition' => [$condition1]
        ]);
        $context = new RetryPolicyContext([
            'retriesAttempted' => 2,
            'exception' => new AEx([
                'errCode' => 'A1Ex',
                'message' => 'a1 error'
            ])
        ]);
        $this->assertEquals(Dara::getBackoffDelay($options, $context), 1000);


        $randomPolicy = BackoffPolicy::newBackoffPolicy([
            'policy' => 'Random',
            'period' => 1000,
            'cap' => 10000,
        ]);
        $condition2 = new RetryCondition([
            'maxAttempts' => 3,
            'exception' => ['AEx'],
            'errorCode' => ['A1Ex'],
            'backoff' => $randomPolicy,
        ]);
        $options = new RetryOptions([
            'retryable' => true,
            'retryCondition' => [$condition2]
        ]);
        $this->assertLessThan(10000, Dara::getBackoffDelay($options, $context));

        $randomPolicy1 = BackoffPolicy::newBackoffPolicy([
            'policy' => 'Random',
            'period' => 10000,
            'cap' => 200,
        ]);
        $condition2 = new RetryCondition([
            'maxAttempts' => 3,
            'exception' => ['AEx'],
            'errorCode' => ['A1Ex'],
            'backoff' => $randomPolicy1,
        ]);
        $context = new RetryPolicyContext([
            'retriesAttempted' => 2,
            'exception' => new BEx([
                'errCode' => 'A1Ex',
                'message' => 'b1 error'
            ])
        ]);
        $options = new RetryOptions([
            'retryable' => true,
            'retryCondition' => [$condition2]
        ]);
        $backoffTime = Dara::getBackoffDelay($options, $context);
        $this->assertTrue($backoffTime >= 100 && $backoffTime <= 200);

        $exponentialPolicy = BackoffPolicy::newBackoffPolicy([
            'policy' => 'Exponential',
            'period' => 5,
            'cap' => 10000,
        ]);
        $condition3 = new RetryCondition([
            'maxAttempts' => 3,
            'exception' => ['AEx'],
            'errorCode' => ['A1Ex'],
            'backoff' => $exponentialPolicy,
        ]);
        $options = new RetryOptions([
            'retryable' => true,
            'retryCondition' => [$condition3],
        ]);
        $this->assertEquals(Dara::getBackoffDelay($options, $context), 1024);

        $exponentialPolicy = BackoffPolicy::newBackoffPolicy([
            'policy' => 'Exponential',
            'period' => 10,
            'cap' => 10000,
        ]);
        $condition4 = new RetryCondition([
            'maxAttempts' => 3,
            'exception' => ['AEx'],
            'errorCode' => ['A1Ex'],
            'backoff' => $exponentialPolicy,
        ]);
        $options = new RetryOptions([
            'retryable' => true,
            'retryCondition' => [$condition4],
        ]);
        $this->assertEquals(Dara::getBackoffDelay($options, $context), 10000);

        $equalJitterPolicy = BackoffPolicy::newBackoffPolicy([
            'policy' => 'EqualJitter',
            'period' => 5,
            'cap' => 10000,
        ]);
        $condition5 = new RetryCondition([
            'maxAttempts' => 3,
            'exception' => ['AEx'],
            'errorCode' => ['A1Ex'],
            'backoff' => $equalJitterPolicy,
        ]);
        $options = new RetryOptions([
            'retryable' => true,
            'retryCondition' => [$condition5],
        ]);
        $backoffTime = Dara::getBackoffDelay($options, $context);
        $this->assertTrue($backoffTime > 512 && $backoffTime < 1024);

        $equalJitterPolicy = BackoffPolicy::newBackoffPolicy([
            'policy' => 'EqualJitter',
            'period' => 10,
            'cap' => 10000,
        ]);
        $condition6 = new RetryCondition([
            'maxAttempts' => 3,
            'exception' => ['AEx'],
            'errorCode' => ['A1Ex'],            
            'backoff' => $equalJitterPolicy,
        ]);
        $options = new RetryOptions([
            'retryable' => true,
            'retryCondition' => [$condition6],
        ]);
        $backoffTime = Dara::getBackoffDelay($options, $context);
        $this->assertTrue($backoffTime > 5000 && $backoffTime < 10000);

        $fullJitterPolicy = BackoffPolicy::newBackoffPolicy([
            'policy' => 'FullJitter',
            'period' => 5,
            'cap' => 10000,
        ]);
        $condition7 = new RetryCondition([
            'maxAttempts' => 2,
            'exception' => ['AEx'],
            'errorCode' => ['A1Ex'],            
            'backoff' => $fullJitterPolicy,
        ]);
        $options = new RetryOptions([
            'retryable' => true,
            'retryCondition' => [$condition7],
        ]);
        $backoffTime = Dara::getBackoffDelay($options, $context);
        $this->assertTrue($backoffTime >= 0 && $backoffTime < 1024);

        $fullJitterPolicy = BackoffPolicy::newBackoffPolicy([
            'policy' => 'ExponentialWithFullJitter',
            'period' => 10,
            'cap' => 10000,
        ]);
        $condition8 = new RetryCondition([
            'maxAttempts' => 2,
            'exception' => ['AEx'],
            'errorCode' => ['A1Ex'],            
            'backoff' => $fullJitterPolicy,
        ]);
        $options = new RetryOptions([
            'retryable' => true,
            'retryCondition' => [$condition8],
        ]);
        $backoffTime = Dara::getBackoffDelay($options, $context);
        $this->assertTrue($backoffTime >= 0 && $backoffTime < 10000);

        $condition9 = new RetryCondition([
            'maxAttempts' => 2,
            'exception' => ['AEx'],
            'errorCode' => ['A1Ex'],            
            'backoff' => $fullJitterPolicy,
            'maxDelay' => 1000
        ]);
        $options = new RetryOptions([
            'retryable' => true,
            'retryCondition' => [$condition9],
        ]);
        $backoffTime = Dara::getBackoffDelay($options, $context);
        $this->assertTrue($backoffTime >= 0 && $backoffTime <= 1000);

        $fullJitterPolicy = BackoffPolicy::newBackoffPolicy([
            'policy' => 'ExponentialWithFullJitter',
            'period' => 10,
            'cap' => 10000 * 10000,
        ]);
        $condition10 = new RetryCondition([
            'maxAttempts' => 2,
            'exception' => ['AEx'],
            'errorCode' => ['A1Ex'],            
            'backoff' => $fullJitterPolicy,
        ]);
        $options = new RetryOptions([
            'retryable' => true,
            'retryCondition' => [$condition10],
        ]);
        $backoffTime = Dara::getBackoffDelay($options, $context);
        $this->assertTrue($backoffTime >= 0 && $backoffTime <= 120 * 1000);

        $context = new RetryPolicyContext([
            'retriesAttempted' => 2,
            'exception' => new CEx([
                'errCode' => 'CEx',
                'message' => 'C error',
                'retryAfter' => 3000
            ])
        ]);

        $condition11 = new RetryCondition([
            'maxAttempts' => 2,
            'exception' => ['CEx'],
            'errorCode' => ['CEx'],            
            'backoff' => $fullJitterPolicy,
            'maxDelay' => 5000,
        ]);
        $options = new RetryOptions([
            'retryable' => true,
            'retryCondition' => [$condition11],
        ]);
        $backoffTime = Dara::getBackoffDelay($options, $context);
        $this->assertEquals($backoffTime, 3000);

        $condition12 = new RetryCondition([
            'maxAttempts' => 2,
            'exception' => ['CEx'],
            'errorCode' => ['CEx'],            
            'backoff' => $fullJitterPolicy,
            'maxDelay' => 1000,
        ]);
        $options = new RetryOptions([
            'retryable' => true,
            'retryCondition' => [$condition12],
        ]);
        $backoffTime = Dara::getBackoffDelay($options, $context);
        $this->assertEquals($backoffTime, 1000);
    }
}

