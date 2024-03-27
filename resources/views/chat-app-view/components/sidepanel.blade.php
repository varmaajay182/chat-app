<div id="sidepanel">
    <div id="profile">
        <div class="wrap">
            <img id="profile-img" src="{{ asset('chat-app/' . $loginUser->image) }}" class="online" alt="" />
            <p>{{ $loginUser->name }}</p>

           
        </div>
    </div>
    {{-- {{isset($unseenMessage)}}
    {{dd(count($unseenMessage))}} --}}
    <div id="search">
        <label for=""><i class="fa fa-search" aria-hidden="true"></i></label>
        <input type="text" placeholder="Search contacts..." />
    </div>
    <div id="contacts">
        <ul>
            @foreach ($users as $user)
                <li class="contact" data-id="{{ $user->id }}" id="user_{{ $user->id }}">
                    <div class="wrap">
                        <span class="contact-status status-offline" id="status-{{ $user->id }}"></span>
                        <img src="{{ asset('chat-app/' . $user->image) }}" alt="" />
                        <div class="meta">
                            <div>
                                <p class="name">{{ $user->name }}</p>
                                <p class="preview" id="preview-{{ $user->id }}"></p>
                                
                            </div>
                          
                            <div class="unseenNumber">
                                
                                <p></p>
                            </div>
                     
                        </div>
                    </div>
                </li>
            @endforeach


        </ul>
    </div>
    <div id="bottom-bar">
        <button id="addcontact"><i class="fa fa-user-plus fa-fw" aria-hidden="true"></i> <span>Add
                contact</span></button>
        <button id="settings" class="d-flex">
            <form method="POST" action="{{ route('logout') }}">
                @csrf

                <x-dropdown-link :href="route('logout')" onclick="event.preventDefault(); this.closest('form').submit();">
                    <i class="fa fa-sign-out" aria-hidden="true"></i>
                </x-dropdown-link>
            </form>
            <span>Logout</span>
        </button>
    </div>

</div>
