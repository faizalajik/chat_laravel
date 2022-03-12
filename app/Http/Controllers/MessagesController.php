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
        if(!$request->user_id) {
            return;
        }

        //         $messages = Message::where(function($query) use ($request) {
        //     $query->where('to_user', 5);
        // })->orWhere(function ($query) use ($request) {
        //     $query->where('to_user', 5);
        // })->orderBy('created_at', 'ASC')->limit(100)->get();

        $messages = Message::where(function($query) use ($request) {
            $query->where('group', 1);
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

        $message->to_user = $request->to_user;

        $message->content = $request->message;
        $message->group = 1;

        $message->save();


        // prepare some data to send with the response
        $message->dateTimeStr = date("Y-m-dTH:i", strtotime($message->created_at->toDateTimeString()));

        $message->dateHumanReadable = $message->created_at->diffForHumans();

        $message->fromUserName = $message->fromUser->name;

        $message->from_user_id = Auth::user()->id;

        $message->toUserName = 'Group1';

        $message->to_user_id = 5;
        $message->group = 1;

        PusherFactory::make()->trigger('chat', 'send', ['data' => $message]);

        return response()->json(['state' => 1, 'data' => $message]);
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
