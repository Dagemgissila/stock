<?php
namespace App\Observers;
use App\Models\StaffMember;
class StaffMemberObserver {
    public function created(StaffMember $sm): void {
        \Log::info('StaffMember assigned: user='.$sm->user_id.' warehouse='.$sm->warehouse_id);
    }
}
