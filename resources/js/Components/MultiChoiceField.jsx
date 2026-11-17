/**
 * 여러 개를 고를 수 있는 선택 칩 묶음. (이상형 외적/내적 조건 등)
 * - value: 선택된 id 배열, onChange: 새 배열을 넘긴다.
 * - exclusiveId 가 있으면 그 항목(예: '상관없음')은 단독으로만 선택된다.
 *   (그 항목을 고르면 나머지가 해제되고, 다른 항목을 고르면 그 항목이 해제된다)
 */
export default function MultiChoiceField({ label, hint, value = [], onChange, options = [], exclusiveId = null, error }) {
    const toggle = (id) => {
        const selected = value.includes(id);

        if (selected) {
            onChange(value.filter((v) => v !== id));
            return;
        }
        if (exclusiveId !== null && id === exclusiveId) {
            onChange([id]);
            return;
        }
        onChange([...value.filter((v) => v !== exclusiveId), id]);
    };

    return (
        <div className="flex flex-col gap-1.5">
            <span className="text-sm font-semibold text-gray-700">
                {label}
                {hint && <span className="ml-1 text-xs font-normal text-gray-400">{hint}</span>}
            </span>
            <div className="flex flex-wrap gap-2">
                {options.map((opt) => {
                    const selected = value.includes(opt.id);

                    return (
                        <button
                            key={opt.id}
                            type="button"
                            aria-pressed={selected}
                            onClick={() => toggle(opt.id)}
                            className={
                                'rounded-full border px-3.5 py-2 text-sm transition active:scale-[0.97] ' +
                                (selected
                                    ? 'border-primary bg-primary-50 font-semibold text-primary'
                                    : 'border-gray-300 bg-white text-gray-700 hover:border-gray-400')
                            }
                        >
                            {selected && <span className="mr-1">✓</span>}
                            {opt.label}
                        </button>
                    );
                })}
            </div>
            {error && <span className="text-xs text-red-500">{error}</span>}
        </div>
    );
}
