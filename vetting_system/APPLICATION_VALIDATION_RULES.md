# Application Creation Validation Rules

## Backend Validation Rules

### Full Name *
- **Required**: Yes
- **Min Length**: 2 characters
- **Max Length**: 255 characters
- **Pattern**: Only letters (a-z, A-Z) and spaces allowed
- **Example**: "John Doe" ✅ | "John123" ❌ | "John-Doe" ❌

### National ID
- **Required**: No (Optional)
- **Length**: Exactly 8 characters (if provided)
- **Pattern**: Only uppercase letters (A-Z) and numbers (0-9)
- **Example**: "ABC12345" ✅ | "abc12345" ❌ | "ABC1234" ❌ (too short)

### Current Name
- **Required**: No (Optional)
- **Max Length**: 255 characters (if provided)
- **Note**: Leave blank for first-time name registration

### Requested Name *
- **Required**: Yes
- **Min Length**: 2 characters
- **Max Length**: 255 characters
- **Must be different from**: Current Name
- **Example**: If current name is "John Smith", requested name must be different

### Reason for Change *
- **Required**: Yes
- **Min Length**: 10 characters
- **Max Length**: 5000 characters
- **Example**: "i need to change" ✅ (17 chars) | "change" ❌ (too short)

## Frontend Form Updates

### Changes Made:
1. ✅ **National ID**: 
   - Changed from required to optional
   - Auto-converts to uppercase as user types
   - Validates 8 characters and uppercase pattern
   - Added helpful hint

2. ✅ **Current Name**: 
   - Changed from required to optional
   - Added hint explaining it's for first-time registrations

3. ✅ **Full Name**: 
   - Added regex validation (letters and spaces only)
   - Added min/max length validation
   - Added helpful hint

4. ✅ **Requested Name**: 
   - Added validation to ensure different from current name
   - Added min/max length validation
   - Added helpful hint

5. ✅ **Reason**: 
   - Added min 10 characters validation
   - Added max 5000 characters validation
   - Added character counter
   - Added helpful hint

6. ✅ **Error Handling**: 
   - Improved error messages from backend
   - Shows specific validation errors
   - Better user feedback

## Common Validation Errors

### "Full name must contain only alphabetic characters and spaces"
- **Cause**: Contains numbers, special characters, or symbols
- **Fix**: Remove numbers and special characters (e.g., "John123" → "John")

### "National ID must be exactly 8 characters with uppercase letters and numbers only"
- **Cause**: 
  - Not exactly 8 characters, OR
  - Contains lowercase letters, OR
  - Contains special characters
- **Fix**: Use format like "ABC12345" (8 uppercase letters/numbers)
- **Note**: Form auto-converts to uppercase, but must be exactly 8 characters

### "Requested name must be different from current name"
- **Cause**: Requested name is the same as current name
- **Fix**: Enter a different name

### "Reason must be at least 10 characters"
- **Cause**: Reason is too short (less than 10 characters)
- **Fix**: Provide more detail (e.g., "I need to change my name due to marriage" instead of "i need to change")

## Example Valid Form Data

```
Full Name: "Chisomo Thindwa"
National ID: "TSRFP72S" (or leave blank)
Current Name: "Chisomo Thindwa" (or leave blank)
Requested Name: "Rio Thindwa"
Reason: "I need to change my name due to marriage. I want to use my spouse's surname."
```

## Testing

After these updates:
1. National ID will auto-uppercase as you type
2. Form validates all rules before submission
3. Clear error messages show what's wrong
4. Optional fields (National ID, Current Name) can be left blank
5. Reason must be at least 10 characters

Try creating an application again with the corrected data!

