export default function Textarea({ 
    label, 
    id, 
    value, 
    onChange, 
    error, 
    required = false, 
    placeholder = "", 
    rows = 4,
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
            <textarea
                id={id}
                value={value}
                onChange={onChange}
                placeholder={placeholder}
                rows={rows}
                className={`mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 sm:text-sm transition-colors resize-vertical ${
                    error ? 'border-red-300 focus:ring-red-500 focus:border-red-500' : ''
                } ${className}`}
                required={required}
                {...props}
            />
            {error && (
                <div className="text-red-500 text-xs mt-1">{error}</div>
            )}
        </div>
    );
}