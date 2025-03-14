<?php

namespace Hacp0012\Quest;

/** @deprecated use `SpawMethod` instead. */
enum QuestSpawMethod
{
  case POST;
  case GET;
  case DELETE;
  case PUT;
  case HEAD;
  case PATCH;
}

/** HTTP request methods. */
enum SpawMethod
{
  case POST;
  case GET;
  case DELETE;
  case PUT;
  case HEAD;
  case PATCH;
}
