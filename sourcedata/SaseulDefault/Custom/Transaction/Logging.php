<?php

namespace Saseul\Custom\Transaction;

use Saseul\Constant\Account;
use Saseul\Constant\Decision;

/**
 * Class Logging.
 *
 * 사용자가 행한 동작을 저장한다.
 */
class Logging extends AbstractTransaction
{
    /** @var string 행위자 */
    private $address;

    /** @var string logging 타입 */
    private $messageType;

    /** @var string logging 메시지 */
    private $message;

    public function initialize($transaction, $thash, $public_key, $signature): void
    {
        parent::initialize($transaction, $thash, $public_key, $signature);

        $this->address = $transaction['address'] ?? '';
        $this->messageType = $transaction['message_type'] ?? '';
        $this->message = $transaction['message'] ?? '';
    }

    public function getValidity(): bool
    {
        return parent::getValidity()
            && $this->isValidUser();
    }

    public function loadStatus(): void
    {
    }

    public function getStatus(): void
    {
    }

    public function setStatus(): void
    {
    }

    public function makeDecision(): string
    {
        return Decision::ACCEPT;
    }

    private function isValidUser(): bool
    {
        return is_string($this->address)
            && (mb_strlen($this->address) === Account::ADDRESS_SIZE);
    }
}
