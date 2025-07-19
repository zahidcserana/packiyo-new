
import { useSearchParams } from "@remix-run/react";
import { MdOutlineKeyboardArrowLeft, MdOutlineKeyboardArrowRight, MdOutlineKeyboardDoubleArrowLeft, MdOutlineKeyboardDoubleArrowRight } from "react-icons/md";
import { Page } from '~/types/automations';

interface PaginationProps  {
    page: Page;
}

export const CURRENT_PAGE = "currentPage";
const FIRST_PAGE = 1;

const Pagination = ({page}: PaginationProps ) => {
    const [searchParams, setSearchParams] = useSearchParams();
    const currentPage = Number(searchParams.get(CURRENT_PAGE)) || 1;
    const isFirstPage = currentPage === FIRST_PAGE;
    const isLastPage = currentPage ===  Number(page?.lastPage);
    const isNextPageAvailable = currentPage < Number(page?.lastPage);
    const isPreviusPageAvailable = FIRST_PAGE < currentPage;


    const handleNextPage = () => {
        if(isNextPageAvailable){
            setSearchParams((prev) => {
                prev.set(CURRENT_PAGE, (currentPage + 1).toString());
                return prev;
            })
        }
    }

    const handlePrevPage = () => {
        if(isPreviusPageAvailable){
            setSearchParams((prev) => {
                prev.set(CURRENT_PAGE, (currentPage - 1).toString());
                return prev;
            })
        }
    }

    const handleFirstPage = () => {
        setSearchParams((prev) => {
            prev.set(CURRENT_PAGE, "1");
            return prev;
        })
    }

    const handleLastPage = () => {
        setSearchParams((prev) => {
            prev.set(CURRENT_PAGE, page?.lastPage);
            return prev;
        })
    }

    const isDisabled = Number(page?.total) === 0;

    return (
        <>
            {page?.total? '' : <p className="text-center p-3 m-0">No data to show.</p>}
            <div className="mt-4 mb-4 d-flex justify-content-center">
                <button
                    onClick={handleFirstPage}
                    className="btn btn-link text-black p-0 mx-2"
                    disabled={isDisabled || isFirstPage}
                    aria-label="First Page"
                >
                    <MdOutlineKeyboardDoubleArrowLeft size={24} />
                </button>
                <button
                    onClick={handlePrevPage}
                    className="btn btn-link text-black p-0 mx-2"
                    disabled={isDisabled || !isPreviusPageAvailable}
                    aria-label="Previous Page"
                >
                    <MdOutlineKeyboardArrowLeft size={24} />
                </button>
                <button
                    onClick={handleNextPage}
                    className="btn btn-link text-black p-0 mx-2"
                    disabled={isDisabled || !isNextPageAvailable}
                    aria-label="Next Page"
                >
                    <MdOutlineKeyboardArrowRight size={24} />
                </button>
                <button
                    onClick={handleLastPage}
                    className="btn btn-link text-black p-0 mx-2"
                    disabled={isDisabled || isLastPage}
                    aria-label="Last Page"
                >
                    <MdOutlineKeyboardDoubleArrowRight size={24} />
                </button>
            </div>
        </>
    )
}

export default Pagination;

