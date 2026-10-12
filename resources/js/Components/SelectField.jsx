export default function SelectField({
    label,
    value,
    onChange,
    options = [],
    placeholder = '선택해주세요',
    error,
    autoFocus = false,
}) {
    return (
        <label className="flex flex-col gap-1.5">
            <span className="text-sm font-semibold text-gray-700">{label}</span>
            <select
                value={value}
                onChange={(e) => onChange(e.target.value)}
                autoFocus={autoFocus}
                className={
                    'rounded-lg border bg-white px-3.5 py-3 text-[15px] outline-none transition focus:ring-2 focus:ring-primary-200 ' +
                    (error ? 'border-red-500' : 'border-gray-300') +
                    (value ? ' text-gray-900' : ' text-gray-400')
                }
            >
                <option value="" disabled>
                    {placeholder}
                </option>
                {options.map((opt) => (
                    <option key={opt.id} value={opt.id} className="text-gray-900">
                        {opt.label}
                    </option>
                ))}
            </select>
            {error && <span className="text-xs text-red-500">{error}</span>}
        </label>
    );
}
