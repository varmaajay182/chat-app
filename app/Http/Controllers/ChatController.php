<?php

namespace App\Http\Controllers;

use App\Events\DeleteMessageEvent;
use App\Events\EditMessageEvent;
use App\Events\MessageHandelEvent;
use App\Events\MessageSeenEvent;
use App\Events\SeenIconUpdateEvent;
use App\Models\ChatMessage;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;

class ChatController extends Controller
{
    public function chatView()
    {
        $users = User::whereNotIn('id', [Auth()->user()->id])->get();
        $loginUser = User::where('id', [Auth()->user()->id])->first();
        // $unseenMessage = ChatMessage::where('sender_id',  $loginUser->id)->whereNull('seen_at')->get();
        // event(new MessageSeenEvent($unseenMessage));
        // dd($unseenMessage);
        return view('chat-app-view.pages.index', ['users' => $users, 'loginUser' => $loginUser]);
    }

    public function saveChat(Request $request)
    {
        try {
            $time = Carbon::now();
            $currentTimeDate = Carbon::parse($time)
                ->setTimezone('Asia/Kolkata')
                ->toDateTimeString();

            $array = explode(" ", $currentTimeDate);

            $currentDate = $array[0];
            $currentTime = $array[1];
            // var_dump($request->sender_id);

            $images = [];
            $chatArray= [];

            if ($request->hasFile('image')) {
                if ($request->file('image') instanceof UploadedFile) {
                    $image = $request->file('image');
                    $imageName = time() . '_' . $image->getClientOriginalName();
                    $image->move(public_path('chat-app/messageImage'), $imageName);
                    $imageUrl = '/chat-app/messageImage/' . $imageName;
                    $images[] = $imageUrl;
                } else {

                    foreach ($request->file('image') as $image) {
                        $imageName = time() . '_' . $image->getClientOriginalName();
                        $image->move(public_path('chat-app/messageImage'), $imageName);
                        $imageUrl = '/chat-app/messageImage/' . $imageName;
                        $images[] = $imageUrl;
                    }
                }
                $message = null;
            } else {
                $message = $request->message;
                $imageUrl= null;
            }

            if($images){
                foreach ($images as $imageUrl) {
                    $chat = ChatMessage::create([
                        'sender_id' => $request->sender_id,
                        'receiver_id' => $request->receiver_id,
                        'message' => $message,
                        'image' => $imageUrl,
                        'message_date' => $currentDate,
                        'message_time' => $currentTime,
                        'updated_at' => null,
                    ]);
    
                    $chatArray[] = $chat;
                }
            }else{
                $chat = ChatMessage::create([
                    'sender_id' => $request->sender_id,
                    'receiver_id' => $request->receiver_id,
                    'message' => $message,
                    'image' => $imageUrl,
                    'message_date' => $currentDate,
                    'message_time' => $currentTime,
                    'updated_at' => null,
                ]);

                $chatArray[] = $chat;
            }

           
              Log::info('Chat Data:', ['data' => $chatArray]);

            $user = User::find($chatArray[0]->sender_id);
            $todayDate = date("Y-m-d");

            $oldData = ChatMessage::where(function ($query) use ($request) {
                $query->where('sender_id', '=', $request->sender_id)
                    ->orWhere('sender_id', '=', $request->receiver_id);
            })->where(function ($query) use ($request) {
                $query->where('receiver_id', '=', $request->sender_id)
                    ->orWhere('receiver_id', '=', $request->receiver_id);
            })->where('message_date', $todayDate)
                ->get();

            event(new MessageHandelEvent($chatArray, $user, $oldData));

            $unseenMessage = ChatMessage::where('sender_id', $request->sender_id)
                ->where('receiver_id', $request->receiver_id)
                ->whereNull('seen_at')
                ->get();

            event(new MessageSeenEvent($unseenMessage));

            return response()->json(['success' => true, 'oldData' => $oldData, 'data' => $chatArray, 'user' => $user]);
        } catch (\Exception $e) {
            // Log::error('Error in Save Chat data:', ['exception' => $e]);
            return response()->json(['error' => 'Error Occurred While Check Data', 'message' => $e->getMessage()], 500);
        }
    }

    public function loadOldChat(Request $request)
    {
        try {

            $data = ChatMessage::where(function ($query) use ($request) {
                $query->where('sender_id', '=', $request->sender_id)
                    ->orWhere('sender_id', '=', $request->receiver_id);
            })->where(function ($query) use ($request) {
                $query->where('receiver_id', '=', $request->sender_id)
                    ->orWhere('receiver_id', '=', $request->receiver_id);
            })->orderBy('message_date', 'ASC')
                ->get();

            $unseenMessage = ChatMessage::where(function ($query) use ($request) {
                $query->where('sender_id', '=', $request->sender_id)
                    ->orWhere('sender_id', '=', $request->receiver_id);
            })->where(function ($query) use ($request) {
                $query->where('receiver_id', '=', $request->sender_id)
                    ->orWhere('receiver_id', '=', $request->receiver_id);
            })->whereNull('seen_at')
                ->get();

            $senderImage = User::where('id', $request->sender_id)->first();
            $receiverImage = User::where('id', $request->receiver_id)->first();

            return response()->json(['success' => true, 'data' => $data, 'senderImage' => $senderImage, 'receiverImage' => $receiverImage, 'unseenMessage' => $unseenMessage]);
        } catch (\Exception $e) {
            Log::error('Error in Check Login:', ['exception' => $e]);
            return response()->json(['error' => 'Error Occurred While get old chat', 'message' => $e->getMessage()], 500);
        }
    }

    public function updateUnseen(Request $request)
    {
        try {
            // Log::info('Chat Data:', ['data' => $request->message]);

            $messages = json_decode($request->message, true);

            if ($messages !== null) {

                // Check if $request->message is an array
                if (is_array($messages)) {
                    // var_dump('sa');
                    if ($request->key == 'click') {
                        // var_dump('2');
                        // $messagesAtIndex0 = array($messages);
                        foreach ($messages as $key => $message) {
                            // var_dump('3');
                            if (isset($message['id'])) {
                                // var_dump('4');
                                $seenMessage = $this->updateMessageSeen($message);
                            }
                        }
                    } else {
                        $messagesAtIndex0 = array($messages);
                        foreach ($messagesAtIndex0 as $key => $message) {

                            if (isset($message['id'])) {
                                $seenMessage = $this->updateMessageSeen($message);
                            }
                        }
                    }

                } else {
                    $this->updateMessageSeen($messages);
                }
            }

            return response()->json(['success' => true, 'message' => 'successfully update', 'seenMessage' => $seenMessage]);

        } catch (\Exception $e) {
            Log::error('Error in get Old chat:', ['exception' => $e]);
            return response()->json(['error' => 'Error Occurred While get old chat', 'message' => $e->getMessage()], 500);
        }
    }

    public function updateMessageSeen($message)
    {

        $unseenMessage = ChatMessage::where('id', $message['id'])
            ->whereNull('seen_at')
            ->first();

        if ($unseenMessage) {
            $unseenMessage->seen_at = now();
            $unseenMessage->save();
        }

        $database_senderId = $message['sender_id'];
        $database_receiverId = $message['receiver_id'];

        event(new SeenIconUpdateEvent($database_senderId, $database_receiverId));

        return $unseenMessage;

    }

    public function deleteMessage(Request $request)
    {
        try {

            $deleteMessage = ChatMessage::where('id', $request->id)->first();

            $oldData = ChatMessage::where('message_date', $deleteMessage->message_date)->get();

            event(new DeleteMessageEvent($deleteMessage, $oldData));

            $deleteMessage->delete();

            return response()->json(['success' => true, 'message' => 'successfully deleted', 'deletedMessage' => $deleteMessage, 'oldData' => $oldData]);

        } catch (\Exception $e) {
            Log::error('Error in While deleteMessage:', ['exception' => $e]);
            return response()->json(['error' => 'Error Occurred While deleteMessage', 'message' => $e->getMessage()], 500);
        }
    }

    public function updateMessage(Request $request)
    {
        try {

            $messageInfo = ChatMessage::where('id', $request->editMessageId)->first();

            $time = Carbon::now();
            $currentTimeDate = Carbon::parse($time)
                ->setTimezone('Asia/Kolkata')
                ->toDateTimeString();

            $array = explode(" ", $currentTimeDate);

            $currentTime = $array[1];

            $messageInfo->update([
                'message' => $request->message,
                'updated_at' => $currentTime,
            ]);

            event(new EditMessageEvent($messageInfo));

            return response()->json(['success' => true, 'message' => 'successfully update', 'updateMessage' => $messageInfo]);

        } catch (\Exception $e) {
            Log::error('Error in While updateing message:', ['exception' => $e]);
            return response()->json(['error' => 'Error Occurred While updateing message', 'message' => $e->getMessage()], 500);
        }
    }

}
