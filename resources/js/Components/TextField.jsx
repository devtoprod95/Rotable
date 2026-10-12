export default function TextField({
    label,
    type = 'text',
    value,
    onChange,
    placeholder,
    error,
    autoFocus = false,
}) {
    return (
        <label className="flex flex-col gap-1.5">
            <span className="text-sm font-semibold text-gray-700">{label}</span>
            <input
                type={type}
                value={value}
                onChange={(e) => onChange(e.target.value)}
                placeholder={placeholder}
                autoFocus={autoFocus}
                className={
                    'rounded-lg border px-3.5 py-3 text-[15px] outline-none transition focus:ring-2 focus:ring-primary-200 ' +
                    (error ? 'border-red-500' : 'border-gray-300')
                }
            />
            {error && <span className="text-xs text-red-500">{error}</span>}
        </label>
    );
}
