<?php

namespace App\Http\Controllers;

use App\Lib\PusherFactory;
use App\Message;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;


class MessagesController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }


    /**
     * getLoadLatestMessages
     *
     *
     * @param Request $request
     */
    public function getLoadLatestMessages(Request $request)
    {
        if(!$request->id_group) {
            return;
        }

        //         $messages = Message::where(function($query) use ($request) {
        //     $query->where('to_user', 5);
        // })->orWhere(function ($query) use ($request) {
        //     $query->where('to_user', 5);
        // })->orderBy('created_at', 'ASC')->limit(100)->get();
        $g = DB::select('SELECT
            users.id, 
            users.`name`, 
            tb_group.id_group, 
            tb_group.name_group
            FROM
            users
            INNER JOIN
            tb_group
            ON 
            users.id = tb_group.id_user
            INNER JOIN
            messages
            ON 
            tb_group.id_group = messages.to_group
            WHERE
            users.id ='.Auth::user()->id.' AND tb_group.id_group ='.$request->id_group.' limit 1');
        // print_r($g[0]->id_group);
        // die;
        $messages = Message::where(function($query) use ($request,$g) {
            $query->where('to_group', $g[0]->id_group);
        })->orderBy('created_at', 'ASC')->get();

        $return = [];

        foreach ($messages as $message) {
            $return[] = view('message-line')->with('message', $message)->render();
        }


        return response()->json(['state' => 1, 'messages' => $return]);
    }


    /**
     * postSendMessage
     *
     * @param Request $request
     */
    public function postSendMessage(Request $request)
    {
        if(!$request->to_user || !$request->message) {
            return;
        }

        $message = new Message();

        $message->from_user = Auth::user()->id;

        $message->to_group = $request->to_user;

        $message->content = $request->message;
        // $message->group = 1;

        $message->save();

        $group = DB::select('SELECT
            users.id, 
            users.`name`, 
            tb_group.id_group, 
            tb_group.name_group
            FROM
            users
            INNER JOIN
            tb_group
            ON 
            users.id = tb_group.id_user
            INNER JOIN
            messages
            ON 
            tb_group.id_group = messages.to_group
            WHERE
            users.id ='.Auth::user()->id.' AND tb_group.id_group ='.$request->to_user.' limit 1');


        // prepare some data to send with the response
        $message->dateTimeStr = date("Y-m-dTH:i", strtotime($message->created_at->toDateTimeString()));

        $message->dateHumanReadable = $message->created_at->diffForHumans();

        $message->fromUserName = $message->fromUser->name;

        $message->from_user_id = Auth::user()->id;

        $message->toUserName = $group[0]->name_group;

        $message->to_group = $request->to_user;

        $user_group = DB::select('SELECT
    t.id_group,
    t.id_user 
FROM
   tb_group t 
WHERE
    t.id_user NOT IN (
    SELECT
        id_user 
    FROM
       tb_group 
WHERE
    id_group IN ( '.$request->to_user.'))
    GROUP BY t.id_user');

        PusherFactory::make()->trigger('chat', 'send', ['data' => $message, 'user_group' => $user_group]);

        return response()->json(['state' => 1, 'data' => $message, 'user_group' => $user_group]);
    }


    /**
     * getOldMessages
     *
     * we will fetch the old messages using the last sent id from the request
     * by querying the created at date
     *
     * @param Request $request
     */
    public function getOldMessages(Request $request)
    {
        if(!$request->old_message_id || !$request->to_user)
            return;

        $message = Message::find($request->old_message_id);

        $lastMessages = Message::where(function($query) use ($request, $message) {
            $query->where('from_user', Auth::user()->id)
            ->where('to_user', $request->to_user)
            ->where('created_at', '<', $message->created_at);
        })
        ->orWhere(function ($query) use ($request, $message) {
            $query->where('from_user', $request->to_user)
            ->where('to_user', Auth::user()->id)
            ->where('created_at', '<', $message->created_at);
        })
        ->orderBy('created_at', 'ASC')->limit(10)->get();

        $return = [];

        if($lastMessages->count() > 0) {

            foreach ($lastMessages as $message) {

                $return[] = view('message-line')->with('message', $message)->render();
            }

            PusherFactory::make()->trigger('chat', 'oldMsgs', ['to_user' => $request->to_user, 'data' => $return]);
        }

        return response()->json(['state' => 1, 'data' => $return]);
    }
}
