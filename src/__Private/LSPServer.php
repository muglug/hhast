<?hh // strict
/*
 *  Copyright (c) 2017-present, Facebook, Inc.
 *  All rights reserved.
 *
 *  This source code is licensed under the MIT license found in the
 *  LICENSE file in the root directory of this source tree.
 *
 */

namespace Facebook\HHAST\__Private;

use type Facebook\CLILib\ITerminal;
use namespace HH\Lib\{Tuple, Str};

final class LSPServer extends LSPLib\Server {
  const keyset<classname<LSPLib\Command>> COMMANDS = keyset[
    LSPImpl\InitializeCommand::class,
  ];

  public function __construct(private ITerminal $terminal) {
    parent::__construct();
  }

  public async function mainAsync(): Awaitable<int> {
    await Tuple\from_async(
      async {
        await $this->mainLoopAsync();
      },
      async {
        await $this->initAsync();
      },
    );
    return 0;
  }

  private async function mainLoopAsync(): Awaitable<void> {
    $stdin = $this->terminal->getStdin();
    while (!$stdin->isEof()) {
      await $this->handleOneAsync();
    }
  }

  private async function initAsync(): Awaitable<void> {
    $this->terminal->getStderr()->write("Waiting for init\n");
    await $this->waitForInitAsync();
    $this->terminal->getStderr()->write("Got Init\n");
    // TODO: lint!
  }

  private async function handleOneAsync(): Awaitable<void> {
    $stdin = $this->terminal->getStdin();
    $length = null;

    // read headers
    while (true) {
      $line = await $stdin->readLineAsync();
      $line = Str\trim($line);
      if ($line === '') {
        break;
      }
      if (!Str\starts_with($line, 'Content-Length')) {
        continue;
      }
      $length = $line
        |> Str\strip_prefix($$, 'Content-Length:')
        |> Str\trim($$)
        |> Str\to_int($$);
    }
    invariant($length !== null, "Expected a content-length header");

    // read body
    $body = '';
    while ($length > 0 && !$stdin->isEof()) {
      $part = await $stdin->readAsync($length);
      $body .= $part;
      $length -= Str\length($part);
      invariant($length >= 0, 'negative bytes remaining');
    }

    $response = await $this->handleMessageAsync($body);
    $this->terminal->getStdout()->write($response);
  }
}