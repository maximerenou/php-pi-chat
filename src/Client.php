<?php

namespace MaximeRenou\PiChat;

class Client
{
    public function createConversation()
    {
        return new Conversation();
    }

    public function resumeConversation($identifiers)
    {
        return new Conversation($identifiers);
    }
}
