<?php

declare(strict_types=1);

/*
 * This file is part of rekalogika/analytics package.
 *
 * (c) Priyadi Iman Nurcahyo <https://rekalogika.dev>
 *
 * For the full copyright and license information, please view the LICENSE file
 * that was distributed with this source code.
 */

namespace Rekalogika\Analytics\Tests\App\Translation;

use PhpParser\Node;
use PhpParser\Node\Expr\New_;
use PhpParser\Node\Name;
use PhpParser\NodeVisitor;
use Rekalogika\Analytics\Util\TranslatableMessage;
use Symfony\Component\Translation\Extractor\Visitor\AbstractVisitor;

final class TranslatableMessageVisitor extends AbstractVisitor implements NodeVisitor
{
    /**
     * @param class-string $class
     */
    public function __construct(
        private readonly string $class = TranslatableMessage::class,
        private readonly string $domain = 'rekalogika_analytics',
    ) {}

    #[\Override]
    public function beforeTraverse(array $nodes): ?Node
    {
        return null;
    }

    #[\Override]
    public function enterNode(Node $node): ?Node
    {
        return null;
    }

    #[\Override]
    public function leaveNode(Node $node): ?Node
    {
        if (!$node instanceof New_) {
            return null;
        }

        if (!($className = $node->class) instanceof Name) {
            return null;
        }

        if ($className->toString() !== $this->class) {
            return null;
        }

        $firstNamedArgumentIndex = $this->nodeFirstNamedArgumentIndex($node);

        if (($messages = $this->getStringArguments($node, 0 < $firstNamedArgumentIndex ? 0 : 'message')) === []) {
            return null;
        }

        foreach ($messages as $message) {
            if (!\is_string($message)) {
                throw new \RuntimeException('The message cannot be null.');
            }

            $this->addMessageToCatalogue($message, $this->domain, $node->getStartLine());
        }

        return null;
    }

    #[\Override]
    public function afterTraverse(array $nodes): ?Node
    {
        return null;
    }
}
