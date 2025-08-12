export default function Checkbox({ 
    label, 
    id, 
    checked, 
    onChange, 
    error, 
    required = false, 
    className = "",
    ...props 
}) {
    return (
        <div className="space-y-1">
            <div className="flex items-center">
                <input
                    id={id}
                    type="checkbox"
                    checked={checked}
                    onChange={onChange}
                    className={`h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded transition-colors ${
                        error ? 'border-red-300' : ''
                    } ${className}`}
                    required={required}
                    {...props}
                />
                {label && (
                    <label 
                        htmlFor={id} 
                        className="ml-2 block text-sm text-gray-900"
                    >
                        {label}
                        {required && <span className="text-red-500 ml-1">*</span>}
                    </label>
                )}
            </div>
            {error && (
                <div className="text-red-500 text-xs mt-1">{error}</div>
            )}
        </div>
    );
}