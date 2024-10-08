<?php

namespace Hacp0012\Quest;

enum QuestSpawMethod
{
  case POST;
  case GET;
  case DELETE;
  case PUT;
  case HEAD;
  case PATCH;
}
