export default function Card({ 
    children, 
    title, 
    className = "",
    padding = "default",
    ...props 
}) {
    const paddingClasses = {
        none: "",
        sm: "p-4",
        default: "p-6",
        lg: "p-8",
    };
    
    return (
        <div 
            className={`bg-white overflow-hidden shadow-sm rounded-lg border border-gray-200 ${className}`}
            {...props}
        >
            {title && (
                <div className="px-6 py-4 border-b border-gray-200">
                    <h3 className="text-lg font-medium text-gray-900">{title}</h3>
                </div>
            )}
            <div className={paddingClasses[padding]}>
                {children}
            </div>
        </div>
    );
}