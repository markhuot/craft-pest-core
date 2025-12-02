# Documentation for AI Agents

This document explains how to maintain and update the documentation in this project.

## Overview

This project uses an automated documentation generation system that extracts PHPDoc comments from source code and converts them into Markdown files for the docs directory. This ensures documentation stays synchronized with the codebase.

## How Documentation Generation Works

### 1. Source Code Documentation

Documentation is written in PHPDoc comments directly in the PHP source files. The system follows this pattern:

**Location**: Source files in `src/` directory (traits, classes, etc.)

**Format**: PHPDoc comments with Markdown-style formatting

**Example** (see `src/test/Queues.php` or `src/test/BrowserHelpers.php`):

```php
/**
 * # Feature Name
 *
 * Overview paragraph explaining what this feature does and why it's useful.
 *
 * ## Installation (if applicable)
 *
 * ```bash
 * composer require package-name --dev
 * ```
 *
 * ## Basic Usage
 *
 * Explanation with code examples:
 *
 * ```php
 * it('does something', function() {
 *     $result = $this->doSomething();
 *     $result->assertSuccess();
 * });
 * ```
 *
 * ## Additional Sections
 *
 * More examples and explanations...
 */
trait FeatureName
{
    /**
     * Method-level documentation.
     *
     * Describes what the method does with examples:
     *
     * ```php
     * $this->methodName('param');
     * ```
     */
    public function methodName(string $param)
    {
        // implementation
    }
}
```

### 2. Documentation Generator Script

**Location**: `src/bin/generate-docs.php`

**Purpose**: Parses PHP files and extracts documentation from PHPDoc comments

**How it works**:
- Takes two arguments: source PHP file path and output Markdown file path
- Uses reflection to read class and method documentation
- Strips PHPDoc comment markers (`/**`, `*`, `*/`, `@param`, etc.)
- Preserves Markdown formatting (headers, code blocks, lists, etc.)
- Outputs clean Markdown files

**Usage**:
```bash
php src/bin/generate-docs.php src/test/BrowserHelpers.php docs/browser-testing.md
```

### 3. GitHub Actions Workflow

**Location**: `.github/workflows/docs.yml`

**Purpose**: Automatically generates documentation on pull requests

**How it works**:
1. Triggers on pull requests
2. Sets up PHP 8.3 environment
3. Installs Composer dependencies
4. Runs `generate-docs.php` for each documented feature
5. Commits and pushes the generated Markdown files back to the branch

**Current documented features**:
- Factories
- Entry/Asset factories
- DOM node lists and forms
- HTTP response assertions
- Element assertions
- Database assertions
- Request builders
- Console response assertions
- Benchmarking
- Cookies
- Authentication (logging in)
- Queue testing
- Snapshots
- **Browser testing** (newly added)
- CLI commands

## Adding New Documentation

When you need to document a new feature, follow these steps:

### Step 1: Write Documentation in Source Code

1. Open the relevant PHP source file (trait, class, behavior, etc.)
2. Add comprehensive PHPDoc comments at the class level
3. Use Markdown formatting for structure:
   - `# Main Title` for the feature name
   - `## Section Headers` for different topics
   - ` ```php ` and ` ``` ` for code blocks
   - ` ```bash ` and ` ``` ` for shell commands
4. Document public methods with their own PHPDoc comments
5. Include practical examples in all documentation

**Key principles**:
- Write from the user's perspective
- Include installation steps if needed
- Show common use cases
- Provide copy-paste ready examples
- Explain WHY, not just HOW

### Step 2: Add to GitHub Actions Workflow

1. Open `.github/workflows/docs.yml`
2. Find the "Generate docs" step (line ~37)
3. Add a new line following the existing pattern:
   ```yaml
   php src/bin/generate-docs.php src/path/to/YourFile.php docs/your-feature.md
   ```
4. Place it logically with related documentation (e.g., testing features together)

### Step 3: Test Locally

Before committing, test that documentation generates correctly:

```bash
php src/bin/generate-docs.php src/path/to/YourFile.php docs/your-feature.md
```

Then review the generated Markdown file:
```bash
cat docs/your-feature.md
```

### Step 4: Commit Changes

Commit both the source file with documentation AND the workflow update:

```bash
git add src/path/to/YourFile.php
git add .github/workflows/docs.yml
git commit -m "Add documentation for new feature"
```

The generated Markdown file (`docs/your-feature.md`) will be created automatically by the GitHub Actions workflow when you create a pull request.

## Documentation Style Guide

### Class/Trait Level Documentation

Start with a main heading and comprehensive overview:

```php
/**
 * # Feature Name
 *
 * 1-2 paragraphs explaining what this feature does, why it exists,
 * and when to use it.
 *
 * ## Installation
 *
 * Include if the feature requires additional packages.
 *
 * ## Basic Usage
 *
 * Always include a simple, working example first.
 *
 * ## Common Patterns
 *
 * Show typical use cases.
 *
 * ## Advanced Usage
 *
 * Optional: Complex scenarios or power-user features.
 */
```

### Method Level Documentation

Focus on practical usage:

```php
/**
 * Brief description of what the method does.
 *
 * Optional longer explanation of behavior or important notes.
 *
 * ```php
 * // Example usage
 * $this->methodName('example');
 * ```
 *
 * @param string $param Description
 * @return mixed Description
 */
```

### Code Examples

- Use complete, runnable examples
- Include context (test wrappers, setup, etc.)
- Add comments to explain non-obvious parts
- Show realistic scenarios, not toy examples

### Formatting

- Use `## ` for section headers (h2)
- Use ` ```php ` for PHP code blocks
- Use ` ```bash ` for shell commands
- Use inline \`code\` for function names, variables, file paths
- Keep lines readable (wrap around 80-100 characters when possible)

## Maintenance Notes

### When to Update Documentation

Update documentation when:
- Adding new public methods
- Changing method signatures
- Modifying behavior
- Adding new features
- Discovering common use cases that should be documented

### When NOT to Update Documentation

Don't document:
- Private/protected methods (unless truly necessary)
- Internal implementation details
- Methods marked with `@internal`
- Magic methods like `__construct`, `__call`, etc. (usually)
- Deprecated features (remove their documentation)

### Reviewing Generated Documentation

After the GitHub Action runs:
1. Check that the Markdown file was created/updated
2. Verify formatting looks correct (headers, code blocks, lists)
3. Ensure code examples are readable
4. Confirm no PHPDoc tags leaked through (like `@param`, `@return`)

## Troubleshooting

### Script fails to generate docs

**Problem**: `generate-docs.php` throws an error

**Solution**:
- Ensure the source file path is correct
- Verify the PHP file has no syntax errors
- Check that the output directory exists

### Markdown formatting is broken

**Problem**: Code blocks or headers don't render correctly

**Solution**:
- Ensure proper spacing around code blocks (blank line before/after)
- Check that code fence markers are ` ``` ` not `` ` ``
- Verify asterisks for lists have space after them

### Method documentation not appearing

**Problem**: Public method docs aren't in the generated Markdown

**Causes**:
- Method might be inherited (only declaring class methods are included)
- Method might not be public
- Method might start with `__` (magic methods excluded)
- Method might have `@internal` tag

### GitHub Action not running

**Problem**: Docs not updating on PR

**Solution**:
- Check that `.github/workflows/docs.yml` is committed
- Verify the workflow has proper permissions
- Check GitHub Actions tab for error messages
- Ensure the branch is not protected against bot commits

## Examples

### Adding Browser Testing Documentation

This was recently added as an example of the full process:

1. **Updated source file**: `src/test/BrowserHelpers.php`
   - Added comprehensive class-level PHPDoc with installation, usage, examples
   - Included sections for basic usage, assertions, debugging, device testing
   - Showed both Craft-specific (`visitTemplate`) and standard Pest features

2. **Updated workflow**: `.github/workflows/docs.yml`
   - Added line: `php src/bin/generate-docs.php src/test/BrowserHelpers.php docs/browser-testing.md`
   - Placed after queue.md and before cli.md

3. **Tested locally**:
   ```bash
   php src/bin/generate-docs.php src/test/BrowserHelpers.php docs/browser-testing.md
   ```

4. **Result**: `docs/browser-testing.md` created with full documentation

Follow this same pattern for future documentation additions.
