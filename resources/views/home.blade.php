@extends('layouts.app')

@section('content')
    <div class="row">
        <div class="col-md-5">
            @if($users->count() > 0)
                <h3>Pick a user to chat with</h3>
                <!-- <ul id="users">
                    @foreach($users as $user)
                        <li><span class="label label-info">{{ $user->name }}</span> <a href="javascript:void(0);" class="chat-toggle" data-id="{{ $user->id }}" data-user="{{ $user->name }}">Open chat</a></li>
                    @endforeach
                </ul> -->
                <?php $arr = array(); 
                $name = array();
                 ?>
                @foreach($group as $g)
                <?php
                array_push($arr, $g->id);
                array_push($name, $g->name);
                ?>
                @endforeach

                <li><span id="roomGroup" class="label label-info">group1</span> <a href="javascript:void(0);" class="chat-toggle" data-id="5" data-user="group">
                Open chat</a></li>
                <input type="hidden" name="name" id="name">
            @else
                <p>No users found! try to add a new user using another browser by going to <a href="{{ url('register') }}">Register page</a></p>
            @endif
        </div>
    </div>

    @include('chat-box')

    <input type="hidden" id="current_user" value="{{ \Auth::user()->id }}" />
    <input type="hidden" id="current_group" value="1" />
    <input type="hidden" id="pusher_app_key" value="{{ env('PUSHER_APP_KEY') }}" />
    <input type="hidden" id="pusher_cluster" value="{{ env('PUSHER_APP_CLUSTER') }}" />
@stop

@section('script')

    <script src="https://js.pusher.com/4.1/pusher.min.js"></script>
    <script src="{{ asset('js/chat.js') }}"></script>

@stop