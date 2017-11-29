<?php

namespace SimplyTestable\ApiBundle\Model;

interface ApplicationStateInterface
{
    const STATE_ACTIVE = 'active';
    const STATE_MAINTENANCE_READ_ONLY = 'maintenance-read-only';
    const STATE_MAINTENANCE_BACKUP_READ_ONLY = 'maintenance-backup-read-only';
    const DEFAULT_STATE = self::STATE_ACTIVE;
}
