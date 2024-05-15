<?php

namespace App\Listeners;

use App\Events\SaveNotification;
use App\Models\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class SendSaveNotification
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  \App\Events\SaveNotification  $event
     * @return void
     */
    public function handle(SaveNotification $event)
    {
        //
        try {
            $event = $event->notification;
            $notification = new Notification();
            $notification->fill($event);
            $notification->save();
        } catch (\Exception $e) {
            errorVisitorLog("Notification", "Notificaton-Save", $event->notification->menu,  null, $e->getMessage());
        }
    }
}
