# Contributing to Local 39 Apprenticeship System

## Development Workflow

1. **Create a feature branch** from `main`
   ```bash
   git checkout -b feature/your-feature-name
   ```

2. **Make your changes** following our coding standards

3. **Test your changes** thoroughly
   ```bash
   php artisan test
   npm run build
   ```

4. **Commit your changes** with clear messages
   ```bash
   git commit -m "feat: add multi-step application form"
   ```

5. **Push your branch** and create a pull request
   ```bash
   git push origin feature/your-feature-name
   ```

## Coding Standards

### PHP/Laravel
- Follow **PSR-12** coding standards
- Use **type hints** for all method parameters and return types
- Write **PHPDoc blocks** for all classes and public methods
- Keep controllers **thin** - move logic to services
- Use **meaningful variable names**

### Vue.js/JavaScript
- Use **Composition API** (not Options API)
- Follow **Vue 3 best practices**
- Use **TypeScript** when possible (future)
- Keep components **focused** and reusable

### Database
- Always create **migrations** for schema changes
- Use **seeders** for test data
- Add **indexes** on frequently queried columns
- Document **complex queries**

## Commit Message Format

Use conventional commits:
- `feat`: New feature
- `fix`: Bug fix
- `docs`: Documentation changes
- `style`: Formatting changes
- `refactor`: Code restructuring
- `test`: Adding tests
- `chore`: Maintenance tasks

Examples:
```
feat: add exam timer component
fix: resolve validation timestamp issue
docs: update API endpoint documentation
```

## Testing

- Write **tests** for all new features
- Ensure **existing tests** still pass
- Aim for **80%+ code coverage**

## Code Review

All pull requests require:
- [ ] Tests passing
- [ ] Code review approval
- [ ] No merge conflicts
- [ ] Updated documentation

## Questions?

Contact the development team lead or create an issue on GitHub.
