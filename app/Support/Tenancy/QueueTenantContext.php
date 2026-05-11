<?php

namespace App\Support\Tenancy;

class QueueTenantContext
{
    public const PAYLOAD_COMPANY_ID = 'tenant_company_id';

    public const PAYLOAD_BYPASS = 'tenant_bypass';

    public function buildPayload(CurrentCompanyContext $context): array
    {
        return [
            self::PAYLOAD_COMPANY_ID => $context->id(),
            self::PAYLOAD_BYPASS => $context->bypassesTenancy(),
        ];
    }

    public function applyPayload(CurrentCompanyContext $context, array $payload): void
    {
        if (! array_key_exists(self::PAYLOAD_COMPANY_ID, $payload) && ! array_key_exists(self::PAYLOAD_BYPASS, $payload)) {
            $this->reset($context);

            return;
        }

        $companyId = $payload[self::PAYLOAD_COMPANY_ID] ?? null;
        $bypass = (bool) ($payload[self::PAYLOAD_BYPASS] ?? false);

        $context->set($companyId !== null ? (int) $companyId : null, $bypass);
    }

    public function reset(CurrentCompanyContext $context): void
    {
        $context->set(null, true);
    }
}
