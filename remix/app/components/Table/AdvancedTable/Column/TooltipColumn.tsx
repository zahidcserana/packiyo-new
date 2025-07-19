import { OverlayTrigger, Stack, Tooltip } from 'react-bootstrap';
import type { TooltipProps } from 'react-bootstrap/Tooltip';

interface ItemWithMoreProps {
  list: string[];
}

const TooltipColumn = ({ list }:ItemWithMoreProps) => {
  const itemSize = list.length;
  const isMoreThanOneItem = itemSize > 1;

  const textStyle = {
    whiteSpace: 'nowrap',
    overflow: 'hidden',
    textOverflow: 'ellipsis',
    maxWidth: '150px'
  };

  const showTooltip = (props: TooltipProps) => (
    <Tooltip id="tooltip" {...props}>
      {list.slice(1).map((item: string, index: number) => (
          <span key={`${item}-${index}`}><span>{item}</span><br/></span>
      ))}
    </Tooltip>
  );

    if (!isMoreThanOneItem) {
        return <span style={textStyle}>{list?.at(0) ?? ""}</span>
  }

  const counterMore = itemSize - 1;

  return (
    <Stack direction="horizontal" gap={0}>
      <div className="me-1" style={textStyle}>{list?.at(0)},</div>
      <OverlayTrigger
        placement="bottom"
        delay={{ show: 250, hide: 400 }}
        overlay={showTooltip}
      >
        <div><span role="button" id="tooltip" className="fw-semibold">+{counterMore}</span></div>
      </OverlayTrigger>
    </Stack>
  );
}

export default TooltipColumn
