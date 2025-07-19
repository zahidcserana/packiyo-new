import "./ToggleSwitch.scss";

interface ToggleSwitchProps {
  id?: string;
  name?: string;
  checked: boolean;
  onChange: (checked: boolean) => void;
  optionLabels?: [string, string];
  small?: boolean;
  disabled?: boolean;
}

const ToggleSwitch = ({
  id,
  name,
  checked,
  onChange,
  optionLabels = ["Active", "Inactive"],
  small,
  disabled
}: ToggleSwitchProps) => {
  function handleKeyPress(e: React.KeyboardEvent<HTMLLabelElement>) {
    if (e.key !== " ") return;

    e.preventDefault();
    onChange(!checked);
  }

  return (
    <div className={"toggle-switch" + (small ? " small-switch" : "")}>
      <input
        type="checkbox"
        name={name}
        className="toggle-switch-checkbox"
        id={id}
        checked={checked}
        onChange={(e) => onChange(e.target.checked)}
        disabled={disabled}
      />
      {id ? (
        <label
          className="toggle-switch-label"
          tabIndex={disabled ? -1 : 1}
          onKeyDown={(e) => handleKeyPress(e)}
          htmlFor={id}
        >
          <span
            className={
              disabled
                ? "toggle-switch-inner toggle-switch-disabled"
                : "toggle-switch-inner"
            }
            data-yes={optionLabels[0]}
            data-no={optionLabels[1]}
            tabIndex={-1}
          />
          <span
            className={
              disabled
                ? "toggle-switch-switch toggle-switch-disabled"
                : "toggle-switch-switch"
            }
            tabIndex={-1}
          />
        </label>
      ) : null}
    </div>
  );
};

export default ToggleSwitch;
