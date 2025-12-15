export default {
    extends: ['@commitlint/config-conventional'],
    rules: {
        // Only allow your 4 specific types
        'type-enum': [2, 'always', ['chore', 'feat', 'fix', 'test']],

        // Type must be lowercase
        'type-case': [2, 'always', 'lower-case'],

        // Type cannot be empty
        'type-empty': [2, 'never'],

        // Subject must start with capital letter (sentence-case)
        'subject-case': [2, 'always', 'sentence-case'],

        // Subject cannot be empty
        'subject-empty': [2, 'never'],

        // Subject should NOT end with period (optional, so set to "never")
        'subject-full-stop': [2, 'never', '.'],

        // Subject length limits (practice rules)
        'subject-max-length': [2, 'always', 72],
        'subject-min-length': [2, 'always', 10],

        // Overall header length
        'header-max-length': [2, 'always', 100],

        // Body and footer line length (practice rules)
        'body-max-line-length': [2, 'always', 200],
        'footer-max-line-length': [2, 'always', 100]
    }
};
