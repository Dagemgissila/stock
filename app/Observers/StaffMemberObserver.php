<?php
namespace App\Observers;
use App\Models\StaffMember;
class StaffMemberObserver{
    public function created(StaffMember $sm):void{\Log::info('StaffMember assigned user='.$sm->user_id.' wh='.$sm->warehouse_id);}
    public function deleted(StaffMember $sm):void{\Log::info('StaffMember removed user='.$sm->user_id);}
}
