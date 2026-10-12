export default function TextField({
    label,
    type = 'text',
    value,
    onChange,
    placeholder,
    error,
    autoFocus = false,
}) {
    const isNumber = type === 'number';

    // 숫자 입력에서 음수/지수/소수점 등 비정수 입력을 막는다.
    function handleKeyDown(e) {
        if (!isNumber) {
            return;
        }

        if (['-', '+', 'e', 'E', '.'].includes(e.key)) {
            e.preventDefault();
        }
    }

    function handleChange(e) {
        if (isNumber) {
            // 숫자 이외 문자를 제거해 음수 기호 등이 남지 않도록 한다.
            onChange(e.target.value.replace(/[^\d]/g, ''));
            return;
        }

        onChange(e.target.value);
    }

    return (
        <label className="flex flex-col gap-1.5">
            <span className="text-sm font-semibold text-gray-700">{label}</span>
            <input
                type={type}
                value={value}
                onChange={handleChange}
                onKeyDown={handleKeyDown}
                placeholder={placeholder}
                autoFocus={autoFocus}
                inputMode={isNumber ? 'numeric' : undefined}
                min={isNumber ? 0 : undefined}
                className={
                    'rounded-lg border px-3.5 py-3 text-base outline-none transition focus:ring-2 focus:ring-primary-200 ' +
                    '[appearance:textfield] [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none ' +
                    (error ? 'border-red-500' : 'border-gray-300')
                }
            />
            {error && <span className="text-xs text-red-500">{error}</span>}
        </label>
    );
}
