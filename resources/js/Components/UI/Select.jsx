export default function Select({ 
    label, 
    id, 
    value, 
    onChange, 
    options = [], 
    error, 
    required = false, 
    placeholder = "Select an option",
    className = "",
    ...props 
}) {
    return (
        <div className="space-y-1">
            {label && (
                <label 
                    htmlFor={id} 
                    className="block text-sm font-medium text-gray-700"
                >
                    {label}
                    {required && <span className="text-red-500 ml-1">*</span>}
                </label>
            )}
            <select
                id={id}
                value={value}
                onChange={onChange}
                className={`mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 sm:text-sm transition-colors ${
                    error ? 'border-red-300 focus:ring-red-500 focus:border-red-500' : ''
                } ${className}`}
                required={required}
                {...props}
            >
                {placeholder && (
                    <option value="" disabled>
                        {placeholder}
                    </option>
                )}
                {options.map((option) => (
                    <option key={option.value} value={option.value}>
                        {option.label}
                    </option>
                ))}
            </select>
            {error && (
                <div className="text-red-500 text-xs mt-1">{error}</div>
            )}
        </div>
    );
}