<?hh // strict
/*
 *  Copyright (c) 2017-present, Facebook, Inc.
 *  All rights reserved.
 *
 *  This source code is licensed under the MIT license found in the
 *  LICENSE file in the root directory of this source tree.
 *
 */

namespace Facebook\HHAST;

final class FinalOrAbstractClassLinterTest extends TestCase {
  use LinterTestTrait;

  protected function getLinter(
    string $file,
  ): Linters\BaseLinter {
    return Linters\FinalOrAbstractClassLinter::fromPath($file);
  }

  public function getCleanExamples(): array<array<string>> {
    return [
      ['<?hh final class test {}'],
      ['<?hh abstract class test {}'],
      ['<?hh interface test {}'],
      ['<?hh trait test {}'],
      ['<?hh final class :page:test-page-1 extends SomeXHPPage {}'],
      ['<?hh abstract final class test {}'],
    ];
  }
}
