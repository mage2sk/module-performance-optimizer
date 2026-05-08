<?php
/**
 * Setup/RecurringData runs on every `bin/magento setup:upgrade`.
 * That gives us a hook to detect install + upgrade transitions —
 * the InstallReporter itself dedups per-version via Magento\Framework\Flag,
 * so re-running setup:upgrade on the same version is a silent no-op.
 *
 * Magento auto-discovers Setup/RecurringData when it implements
 * InstallDataInterface; no DI configuration needed.
 */
declare(strict_types=1);

namespace Panth\PerformanceOptimizer\Setup;

use Magento\Framework\Setup\InstallDataInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Panth\PerformanceOptimizer\Service\InstallReporter;

class RecurringData implements InstallDataInterface
{
    public function __construct(
        private readonly InstallReporter $reporter
    ) {
    }

    public function install(ModuleDataSetupInterface $setup, ModuleContextInterface $context): void
    {
        $this->reporter->reportInstall();
    }
}
