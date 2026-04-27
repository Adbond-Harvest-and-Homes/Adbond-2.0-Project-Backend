<?php 

namespace app\Domain\Orders\DTOs;

// app/Domain/Orders/DTOs/PrepareOrderDTO.php
final class PrepareOrderDTO
{
    public function __construct(
        public readonly int     $packageId,
        public readonly int     $clientId,
        public readonly float   $units,
        public readonly bool    $isInstallment,
        public readonly ?int    $installmentCount,
        public readonly ?float  $firstPaymentAmount,
        public readonly ?string $promoCode,
        public readonly ?string $redemptionOption,
        public readonly ?int    $processingId,
    ) {}

    public static function fromRequest(PrepareOrder $request, int $clientId): self
    {
        $v = $request->validated();
        return new self(
            packageId:           $v['packageId'],
            clientId:            $clientId,
            units:               $v['units'],
            isInstallment:       $v['isInstallment'],
            installmentCount:    $v['installmentCount'] ?? null,
            firstPaymentAmount:  $v['amount'] ?? null,
            promoCode:           $v['promoCode'] ?? null,
            redemptionOption:    $v['redemptionOption'] ?? null,
            processingId:        $v['processingId'] ?? null,
        );
    }
}