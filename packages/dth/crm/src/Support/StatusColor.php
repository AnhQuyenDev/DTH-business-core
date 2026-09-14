<?php

namespace Dth\Crm\Support;

final class StatusColor
{
	public static function for(mixed $status): string
	{
		$value = $status instanceof \BackedEnum ? $status->value : (string) $status;

		return match ($value) {
			'active', 'working', 'qualified', 'converted', 'customer', 'accepted', 'completed', 'verified' => 'success',
			'spam', 'unqualified', 'rejected', 'inactive', 'closed', 'failed', 'absent', 'leave', 'sick' => 'danger',
			'duplicate', 'follow_up', 'pending', 'new', 'draft', 'half_day' => 'warning',
			'assigned', 'contacting', 'prospect', 'processing', 'remote' => 'info',
			default => 'gray',
		};
	}
}
